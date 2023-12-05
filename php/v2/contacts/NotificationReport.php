<?php

namespace NW\WebService\References\Operations\Notification\contacts;

class NotificationReport
{
    public bool $notificationEmployeeByEmail;
    public bool $notificationClientByEmail;
    public bool $notificationClientBySms;
    public ?string $notificationClientBySmsError;
}