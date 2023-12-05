<?php

namespace NW\WebService\References\Operations\Notification\contacts\dto;

use DateTime;
use NW\WebService\References\Operations\Notification\Contractor;
use NW\WebService\References\Operations\Notification\Employee;
use NW\WebService\References\Operations\Notification\Seller;

class NotificationAboutEventDto
{
    public int $eventType;
    public Seller $seller;
    public Contractor $client;
    public Employee $creator;
    public Employee $expert;
    public int $complainId;
    public string $complainNumber;
    public int $consumptionId;
    public string $consumptionNumber;
    public string $agreementNumber;
    public DateTime $date;

    public int $fromStatus;
    public int $toStatus;
}