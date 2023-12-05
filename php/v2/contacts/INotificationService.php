<?php

namespace NW\WebService\References\Operations\Notification\contacts;

use NW\WebService\References\Operations\Notification\contacts\dto\NotificationAboutEventDto;

interface INotificationService
{
    const EVENT_TYPE_NEW_POSITION = 1;
    const EVENT_TYPE_CHANGE_STATUS_POSITION = 2;

    /**
     * Оповещает нужных пользователей о произошедгем событии
     *
     * @param NotificationAboutEventDto $dto
     * @return NotificationReport
     */
    function notificationAboutChangePosition(NotificationAboutEventDto $dto): NotificationReport;
}