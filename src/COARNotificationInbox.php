<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

require_once __DIR__ . '/../orm/COARNotification.php';
require_once __DIR__ . '/../orm/COARNotificationException.php';

// See https://rhiaro.co.uk/2017/08/diy-ldn for a very basic walkthrough of an ldn-inbox
// done by Amu Guy who wrote the spec.


/**
 *
 *
 * @throws COARNotificationException
 */
function validate_notification($notification_json) {
    // Validating that @context has ActivityStreams and Coar notify namespaces.
    if(!isset($notification_json['@context'])) {
        throw new COARNotificationException("The notification must include a '@context' property.");
    }

    if(!count(preg_grep("#^http[s]?://www.w3.org/ns/activitystreams$#", $notification_json['@context']))) {
        throw new COARNotificationException("The '@context' property must include Activity Streams 2.0 (https://www.w3.org/ns/activitystreams).");
    }

    if(!count(preg_grep("#^http[s]?://purl.org/coar/notify$#", $notification_json['@context']))) {
        throw new COARNotificationException("The '@context' property must include Notify (https://purl.org/coar/notify).");
    }

    // Validating that id must not be empty
    $mandatory_properties = ['id'];

    foreach($mandatory_properties as $mandatory_property) {
        if($notification_json[$mandatory_property] === '') {
            throw new COARNotificationException("$mandatory_property is empty.");
        }

    }
}

class COARNotificationInbox
{
    private EntityManager $entityManager;
    public Logger $logger;
    public string $id;
    public string $inbox_url;
    public int $timeout;
    public array $accepted_formats;
    public string $user_agent;

    /**
     * @throws COARNotificationException
     * @throws \Doctrine\ORM\ORMException
     */
    public function __construct($db_user, $db_password, $db_name='coar_inbox', $id=null, $inbox_url=null, $timeout=5,
                                $accepted_formats=array('application/ld+json'),
                                $log_level=Logger::DEBUG, $user_agent='PHP Coar notify library')
    {
        if(!(isset($db_user) && isset($db_password)))
            throw new COARNotificationException('A database username and password required.');

        $this->id = $id ?? $_SERVER['SERVER_NAME'];
        $this->inbox_url = $inbox_url ?? $_SERVER['PHP_SELF'];
        $this->timeout = $timeout;
        $this->accepted_formats = $accepted_formats;
        $this->user_agent = $user_agent;

        $this->logger = new Logger('NotifyCOARLogger');
        $this->logger->pushHandler(new RotatingFileHandler('./log/NotifyCOARLogger.log',
            0, $log_level, true, 0664));

        $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/orm"),
            true, null, null, false);
        $conn = array('host'     => '127.0.0.1',
            'driver'   => 'pdo_mysql',
            'user'     => $db_user,
            'password' => $db_password,
            'dbname'   => $db_name,
        );

        $this->entityManager = EntityManager::create($conn, $config);

        // $this->do_response();

    }

    public function do_response() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET')   {
            http_response_code(403);
        }

        else if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        // See https://www.w3.org/TR/2017/REC-ldn-20170502/#sender
            header("Allow: " . implode(', ', ['POST', 'OPTIONS']));
            header("Accept-Post: " . implode(', ', $this->accepted_formats));

        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // See https://www.w3.org/TR/2017/REC-ldn-2COARTarget0170502/#sender
            if (str_starts_with($_SERVER["CONTENT_TYPE"], 'application/ld+json')) {
                // Could be followed by a 'profile' but that's not actioned on
                // https://datatracker.ietf.org/doc/html/rfc6906

                $this->logger->debug('Received a ld+json POST request.');

                // Validating JSON and keeping the variable
                // Alternative is to load into EasyRDF, the go to rdf library for PHP,
                // or the more lightweight and ActivityStreams-specific ActivityPhp
                // This is a computationally expensive operation that should be done
                // at a later stage.

                $notification_json = null;

                try {
                    $notification_json = json_decode(
                        file_get_contents('php://input'), true, 512,
                        JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    $this->logger->error("Syntax error: Badly formed JSON in payload.");
                    $this->logger->debug($exception->getTraceAsString());
                    http_response_code(400);
                    return;
                }

                try {
                    validate_notification($notification_json);
                } catch (COARNotificationException $exception) {
                    $this->logger->error("Invalid notification: " . $exception->getMessage());
                    $this->logger->debug($exception->getTraceAsString());
                    $this->logger->debug((print_r($notification_json, true)));
                    http_response_code(422);
                    return;
                }

                // Creating an inbound ORM object
                try {
                    $notification = new COARNotification($this->logger);
                    $notification->setId(json_encode($notification_json['id']) ?? '');
                    $notification->setType(json_encode($notification_json['type']) ?? '');
                    $notification->setOriginal(json_encode($notification_json));
                    $notification->setStatus(201);
                } catch (COARNotificationException $exception) {
                    $this->logger->error($exception->getMessage());
                    http_response_code(422);
                    return;
                }

                // Committing to database
                try {
                    $this->entityManager->persist($notification);
                    $this->entityManager->flush();
                    $this->logger->info("Wrote inbound notification (ID: " . $notification->getId() . ") to database.");
                } catch (Exception $exception) {
                    // Trouble catching PDOExceptions
                    //if($exception->getCode() == 1062) {
                    $this->logger->error($exception->getMessage());
                    $this->logger->debug($exception->getTraceAsString());

                    http_response_code(422);
                    return;
                    //}

                }

                //header("Location: " . $config['inbox_url']);
                http_response_code(201);

            } else {
                $this->logger->debug("415 Unsupported Media Type: received a POST but content type '"
                    . $_SERVER["CONTENT_TYPE"] . "' not an accepted format.");
                http_response_code(415);
            }

        }
    }

    public function announceEndorsement(COARNotificationActor $cActor, COARNotificationObject $cObject,
                                        COARNotificationContext $cContext, COARNotificationTarget $cTarget) {
        $notification = new OutboundCOARNotification($this->logger, $this->id, $this->inbox_url, $this->timeout,
            $this->user_agent, $cActor, $cObject, $cContext,  $cTarget);
        $notification->announceEndorsement();
        $this->persistOutboundNotification($notification);
    }

    public function requestReview(COARNotificationActor $cActor, COARNotificationObject $cObject,
                                  COARNotificationContext $cContext, COARNotificationTarget $cTarget) {
        $notification = new OutboundCOARNotification($this->logger, $this->id, $this->inbox_url, $this->timeout,
            $this->user_agent, $cActor, $cObject, $cContext,  $cTarget);
        $notification->requestReview();
        $this->persistOutboundNotification($notification);
    }

    private function persistOutboundNotification($notification) {
        try {
            $this->entityManager->persist($notification);
            $this->entityManager->flush();
            $this->logger->info("Wrote outbound notification (ID: " . $notification->getId() . ") to database.");
        }
        catch (Exception $exception) {
            // Trouble catching PDOExceptions
            //if($exception->getCode() == 1062) {
            $this->logger->error($exception->getMessage());
            $this->logger->debug($exception->getTraceAsString());
            return;
            //}

        }
    }
}

