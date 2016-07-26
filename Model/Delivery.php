<?php

namespace SerendipityHQ\Bundle\AwsSesMonitorBundle\Model;

/**
 * Represents a Delivery.
 */
class Delivery
{
    /**
     * @var string
     */
    protected $emailAddress;

    /**
     * @var \DateTime
     */
    protected $deliveryTime;

    /**
     * @param $emailAddress
     */
    public function __construct($emailAddress)
    {
        $this->emailAddress = mb_strtolower($emailAddress);
    }

    /**
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @return \DateTime
     */
    public function getDeliveryTime()
    {
        return $this->deliveryTime;
    }

    /**
     * @param \DateTime $deliveryTime
     *
     * @return $this
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->deliveryTime = $deliveryTime;

        return $this;
    }
}