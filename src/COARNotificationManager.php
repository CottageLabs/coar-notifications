<?php
namespace cottagelabs\coarNotifications;

use cottagelabs\coarNotifications\orm\COARNotification;
use cottagelabs\coarNotifications\orm\COARNotificationException;
use cottagelabs\coarNotifications\orm\COARNotificationNoDatabaseException;
use cottagelabs\coarNotifications\orm\OutboundCOARNotification;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Setup;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use JsonException;
use Monolog\Logger;


// See https://rhiaro.co.uk/2017/08/diy-ldn for a very basic walkthrough of an ldn-inbox
// done by Amu Guy who wrote the spec.

/**
 *  This validation functions ensures that there is a '@context' property that includes
 *  ActivityStreams 2.0 and COAR Notify.
 *  Also checks for the existence of a 'id' property.
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

class COARNotificationManager
{
    private EntityManager $entityManager;
    public Logger $logger;
    public string $id;
    public string $inbox_url;
    public int $timeout;
    public array $accepted_formats;
    public string $user_agent;
    private bool $connected = false;

    /**
     * A COARNotificationManager can either receive or send COAR Notifications.
     * Only required parameter is either database connection parameters or a connection.
     * @throws COARNotificationException
     * @throws ORMException
     */
    public function __construct($conn, $start_inbox=True, Logger $logger=null, string $id=null, string $inbox_url=null,
                                //$accepted_formats=array('application/ld+json'),
                                $timeout=5, $user_agent='PHP COAR Notification Manager')
    {
        if(!(is_array($conn) || $conn instanceof Connection))
            throw new COARNotificationNoDatabaseException('Either a database connection or' .
                'a database configuration is required.');

        if(isset($logger))
            $this->logger = $logger;

        $this->id = $id ?? $_SERVER['SERVER_NAME'];
        $this->inbox_url = $inbox_url ?? $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

        //if(!is_array($accepted_formats))
        //    throw new InvalidArgumentException("'accepted_formats' argument must be an array.");

        $this->accepted_formats = array('application/ld+json'); //$accepted_formats;

        // Timeout and user agent are only relevant for outbound notifications
        $this->timeout = $timeout;
        $this->user_agent = $user_agent;

        $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/src/orm"),
            false, null, null, false);

        $this->entityManager = EntityManager::create($conn, $config);

        // Verifying database connection
        try {
            $this->entityManager->getConnection()->connect();

            if(isset($this->logger))
                $this->logger->debug("Database connection verified.");
            $this->connected = true;
        } catch (Exception $e) {
            // Printing this exception if logger is not available as this is a fatal
            // initialisation error
            if(isset($this->logger))
                $this->logger->error("Couldn't establish a database connection: " . $e);
            else
                print("Couldn't establish a database connection: " . $e);
            return;
        }

        $this->logger->debug('Received a request.');

        if($start_inbox)
            $this->do_response();

    }

    public function get_notifications(): \Doctrine\Persistence\ObjectRepository
    {
        return $this->entityManager->getRepository(COARNotification::class);
    }

    public function __toString(): string {
        return static::class . $this->id;
    }

    /**
     * Handles incoming requests.
     * @throws COARNotificationException
     */
    public function do_response() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        // See https://www.w3.org/TR/2017/REC-ldn-20170502/#sender
            header("Allow: " . implode(', ', ['POST', 'OPTIONS']));
            header("Accept-Post: " . implode(', ', $this->accepted_formats));

        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // See https://www.w3.org/TR/2017/REC-ldn-2COARTarget0170502/#sender
            if (str_starts_with($_SERVER["CONTENT_TYPE"], 'application/ld+json')) {
                // Could be followed by a 'profile' but that's not actioned on
                // https://datatracker.ietf.org/doc/html/rfc6906

                if(isset($this->logger))
                    $this->logger->debug('Received a ld+json POST request.');

                if(!$this->connected)
                    throw new COARNotificationNoDatabaseException();

                // Validating JSON and keeping the variable
                // Alternative is to load into EasyRDF, the go to rdf library for PHP,
                // or the more lightweight and ActivityStreams-specific ActivityPhp
                // This is a computationally expensive operation that should be done
                // at a later stage.

                try {
                    $notification_json = json_decode(
                        file_get_contents('php://input'), true, 512,
                        JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    if(isset($this->logger)) {
                        $this->logger->error("Syntax error: Badly formed JSON in payload.");
                        $this->logger->debug($exception->getTraceAsString());
                    }
                    http_response_code(400);
                    return;
                }

                try {
                    validate_notification($notification_json);
                } catch (COARNotificationException $exception) {
                    if(isset($this->logger)) {
                        $this->logger->error("Invalid notification: " . $exception->getMessage());
                        $this->logger->debug($exception->getTraceAsString());
                        $this->logger->debug((print_r($notification_json, true)));
                    }
                    http_response_code(422);
                    return;
                }

                // Creating an inbound ORM object
                try {
                    $notification = new COARNotification($this->logger);
                    $notification->setId($notification_json['id'] ?? '');
                    $notification->setFromId($notification_json['origin']['id'] ?? '');
                    $notification->setToId($notification_json['target']['id'] ?? '');

                    if($notification_json['type'])
                        $notification->setType($notification_json['type']);

                    $notification->setOriginal($notification_json);
                    $notification->setStatus(201);
                } catch (COARNotificationException | Exception $exception) {
                    if(isset($this->logger)) {
                        $this->logger->error($exception->getMessage());
                        $this->logger->debug($exception->getTraceAsString());
                    }
                    http_response_code(422);
                    return;
                }

                // Committing to database

                $this->persistNotification($notification);

                //header("Location: " . $config['inbox_url']);
                http_response_code(201);

            } else {
                if(isset($this->logger))
                    $this->logger->debug("415 Unsupported Media Type: received a POST but content type '"
                        . $_SERVER["CONTENT_TYPE"] . "' not an accepted format.");
                http_response_code(415);
            }

        }
    }

    /**
     * @throws Exception
     */
    public function createOutboundNotification($cNActor, $cObject, $cContext, $cTarget): OutboundCOARNotification
    {
        return new OutboundCOARNotification($this->logger, $this->id, $this->inbox_url,
            $cNActor, $cObject, $cContext, $cTarget);

    }

    /**
     * Author requests review with possible endorsement (via overlay journal)
     * Implements step 3 of scenario 1
     * https://notify.coar-repositories.org/scenarios/1/5/
     * @param OutboundCOARNotification $outboundCOARNotification
     * @param null $inReplyToId
     * @throws COARNotificationException
     * @throws COARNotificationNoDatabaseException
     */
    public function announceEndorsement(OutboundCOARNotification $outboundCOARNotification, $inReplyToId = null) {
        if(!$this->connected)
            throw new COARNotificationNoDatabaseException();

        if(!empty($inReplyToId))
            $outboundCOARNotification->setInReplyToId($inReplyToId);

        $outboundCOARNotification->setType(["Announce", "coar-notify:EndorsementAction"]);

        $this->send($outboundCOARNotification);
        $this->persistNotification($outboundCOARNotification);
    }

    /**
     * Author requests review with possible endorsement (via overlay journal)
     * Implements step 3 of scenario 1
     * https://notify.coar-repositories.org/scenarios/1/3/
     * @param OutboundCOARNotification $outboundCOARNotification
     * @param null $inReplyToId
     * @throws COARNotificationException
     * @throws COARNotificationNoDatabaseException
     */
    public function announceReview(OutboundCOARNotification $outboundCOARNotification, $inReplyToId = null) {
        if(!$this->connected)
            throw new COARNotificationNoDatabaseException();

        // Special case of step 4 scenario 2
        // https://notify.coar-repositories.org/scenarios/2/4/
        if(!empty($inReplyToId))
            $outboundCOARNotification->setInReplyToId($inReplyToId);

        $outboundCOARNotification->setType(["Announce", "coar-notify:ReviewAction"]);

        $this->send($outboundCOARNotification);
        $this->persistNotification($outboundCOARNotification);
    }

    /**
     * Author requests review with possible endorsement (via repository)
     * Implements step 3 of scenario 2
     * https://notify.coar-repositories.org/scenarios/2/2/
     * @throws COARNotificationException
     */
    public function requestReview(OutboundCOARNotification $outboundCOARNotification) {
        if(!$this->connected)
            throw new COARNotificationNoDatabaseException();

        $outboundCOARNotification->setType(["Offer", "coar-notify:ReviewAction"]);

        $this->send($outboundCOARNotification);
        $this->persistNotification($outboundCOARNotification);
    }

    /**
     * todo Handle send HTTP errors
     */
    private function send(OutboundCOARNotification $outboundCOARNotification) {
        $this->logger->debug("Sending notification.");

        // TODO Disable 400-500 HTTP errors being raised ['http_errors' => false]
        $client = new \GuzzleHttp\Client();

        try {
            $res = $client->request('POST', $outboundCOARNotification->getTargetURL(), [
                'connect_timeout' => $this->timeout,
                'headers' => ['Content-Type' => 'application/ld+json',
                    'User-Agent' => $this->user_agent],
                'body' => $outboundCOARNotification->getJSON(),
            ]);

            $outboundCOARNotification->setStatus($res->getStatusCode());
        }
        catch (ConnectException | RequestException $e) {
            // Guzzle will use cURL by default, we can therefore expect a ContextHandler with
            // cURL error codes
            $ctx = $e->getHandlerContext();
            $this->logger->error('Error');


            if(in_array('errno', $ctx)) {
                $error_no = $ctx['errno'];
                $outboundCOARNotification->setStatus($error_no);

                $msg = "Outbound notification (ID: " . $outboundCOARNotification->getId() . ") could not be sent.";
                // Timed out?
                if($error_no === 7)
                    $msg .= " Couldn't connect to " . $outboundCOARNotification->getTargetURL();
                else if($error_no === 28)
                    $msg .= " Timed out.";
                else
                    $msg .= " cURL error no: " . $error_no;

                $this->logger->error($msg);

            }
            else {
                $this->logger->error($e);
            }

        }

    }

    private function persistNotification($notification) {
        try {
            $this->entityManager->persist($notification);
            $this->entityManager->flush();

            if(isset($this->logger)) {
                $msg = 'Wrote ';

                if(is_null($notification->getStatus()) ||
                    ($notification->getStatus() < 200 || $notification->getStatus() > 299)) {
                    $msg .= "failed ";

                    if(!is_null($notification->getStatus()))
                        $msg .= "(" . $notification->getStatus() . ") ";
                }

                if ($notification instanceof OutboundCOARNotification)
                    $msg .= "outbound";
                else
                    $msg .= "inbound";

                $this->logger->info($msg . " notification (ID: " . $notification->getId() . ") to database.");
            }
        }
        catch (Exception $exception) {
            if(isset($this->logger)) {
                // Trouble catching PDOExceptions
                //if($exception->getCode() == 1062) {
                $this->logger->error($exception->getMessage());
                $this->logger->debug($exception->getTraceAsString());
                //}
            }

            // If an inbound notification can't be written to database
            // then we issue a 422
            if(get_class($notification) != "OutboundCOARNotification")
                http_response_code(422);

        }
    }

    public function removeNotificationById(string $notificationId): void
    {

        $notificationToRemove = $this->entityManager->getReference(COARNotification::class, $notificationId);

        try {
            $this->entityManager->remove($notificationToRemove);
            $this->entityManager->flush();

            if (isset($this->logger)) {
                $this->logger->info(sprintf("Removing notification (ID: %s) from database.", $notificationId));
            }
        } catch (Exception $exception) {
            if (isset($this->logger)) {
                $this->logger->error($exception->getMessage());
                $this->logger->debug($exception->getTraceAsString());
            }
        }
    }

}
