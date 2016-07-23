<?php
namespace SerendipityHQ\Bundle\AwsSesMonitorBundle\Plugin;

use Doctrine\Common\Persistence\ObjectManager;
use SerendipityHQ\Bundle\AwsSesMonitorBundle\Model\Bounce;
use SerendipityHQ\Bundle\AwsSesMonitorBundle\Model\BounceRepositoryInterface;
use SerendipityHQ\Bundle\AwsSesMonitorBundle\Model\Complaint;
use SerendipityHQ\Bundle\AwsSesMonitorBundle\Model\ComplaintRepositoryInterface;
use Swift_Events_SendEvent;

/**
 * The SwiftMailer filter.
 */
class MonitorFilterPlugin implements \Swift_Events_SendListener
{
    /** @var BounceRepositoryInterface */
    private $bounceRepo;

    /** @var ComplaintRepositoryInterface */
    private $complaintRepo;

    /** @var  array */
    private $blacklisted = [];

    /** @var bool $filterNotBlacklisted */
    private $filterNotBlacklisted;

    /** @var  int $numberOfBouncesForBlacklist */
    private $numberOfBouncesForBlacklist;

    /**
     * @param ObjectManager $manager
     * @param bool $filterNotBlacklisted
     * @param int $numberOfBouncesForBlacklist
     */
    public function __construct(ObjectManager $manager, $filterNotBlacklisted, $numberOfBouncesForBlacklist)
    {
        $this->bounceRepo = $manager->getRepository('AwsSesMonitorBundle:Bounce');
        $this->complaintRepo = $manager->getRepository('AwsSesMonitorBundle:Complaint');
        $this->filterNotBlacklisted = $filterNotBlacklisted;
        $this->numberOfBouncesForBlacklist = $numberOfBouncesForBlacklist;
    }

    /**
     * Invoked immediately before the Message is sent.
     *
     * @param Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();

        $message->setTo($this->filterForBlacklisted($message->getTo()));
        $message->setCc($this->filterForBlacklisted($message->getCc()));
        $message->setBcc($this->filterForBlacklisted($message->getBcc()));
    }

    /**
     * Invoked immediately after the Message is sent.
     *
     * @param Swift_Events_SendEvent $evt
     */
    public function sendPerformed(Swift_Events_SendEvent $evt)
    {
        $evt->setFailedRecipients(array_keys($this->blacklisted));
    }

    /**
     * @param $recipients
     * @return mixed
     */
    private function filterForBlacklisted($recipients)
    {
        if (!is_array($recipients)) {
            return $recipients;
        }

        $emails = array_keys($recipients);

        foreach ($emails as $email) {
            if ($this->isBounced($email) || $this->isCoplained($email)) {
                $this->blacklisted[$email] = $recipients[$email];
                unset($recipients[$email]);
            }
        }

        return $recipients;
    }

    /**
     * @param $email
     * @return bool
     */
    private function isBounced($email)
    {
        $bounce = $this->bounceRepo->findBounceByEmail($email);
        if ($bounce instanceof Bounce) {
            if ($bounce->isPermanent() || $this->filterNotBlacklisted ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $email
     * @return bool
     */
    private function isCoplained($email)
    {
        $complaint = $this->complaintRepo->findComplaintByEmail($email);
        if ($complaint instanceof Complaint) {

            if ($complaint->isPermanent() || $this->filterNotBlacklisted) {
                return true;
            }
        }

        return false;
    }
}