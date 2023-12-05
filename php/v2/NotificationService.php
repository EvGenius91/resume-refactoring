<?php

namespace NW\WebService\References\Operations\Notification;

use NW\WebService\References\Operations\Notification\contacts\dto\NotificationAboutEventDto;
use NW\WebService\References\Operations\Notification\contacts\INotificationService;
use NW\WebService\References\Operations\Notification\contacts\NotificationReport;
use NW\WebService\References\Operations\Notification\exceptions\NotificationServiceBaseException;

class NotificationService implements INotificationService
{
    /**
     * @param NotificationAboutEventDto $dto
     * @return NotificationReport
     * @throws NotificationServiceBaseException
     */
    function notificationAboutChangePosition(NotificationAboutEventDto $dto): NotificationReport
    {
        $result = new NotificationReport();
        $result->notificationEmployeeByEmail = false;
        $result->notificationClientByEmail = false;
        $result->notificationClientBySms = false;
        $result->notificationClientBySmsError = null;

        $templateVariables = $this->buildTemplateVariables($dto);
        $emailFrom = getResellerEmailFrom($dto->client->id);

        if (!empty($emailFrom)) {
            $result->notificationEmployeeByEmail = $this->sendNotificationToEmployers(
                $dto->seller,
                $emailFrom,
                $templateVariables
            );
        }



        if ($dto->eventType === self::EVENT_TYPE_CHANGE_STATUS_POSITION) {
            if ($emailFrom) {
                $result->notificationClientByEmail = $this->sendEmailNotificationNewStatusToClient(
                    $dto->toStatus,
                    $dto->seller,
                    $dto->client,
                    $emailFrom,
                    $templateVariables
                );
            }


            if ($dto->client->mobile) {
                $sendSmsReport = $this->sendSmsNotificationNewStatusToClient(
                    $dto->seller,
                    $dto->client,
                    $dto->toStatus,
                    $templateVariables
                );

                $result->notificationClientBySms = $sendSmsReport['isSend'];
                $result->notificationClientBySmsError = $sendSmsReport['errorMessage'];
            }
        }

        return $result;
    }

    /**
     * Билдит строку с изменениями
     *
     * @param NotificationAboutEventDto $dto
     * @return string
     * @throws NotificationServiceBaseException
     */
    protected function buildDifferences(NotificationAboutEventDto $dto): string
    {
        /**
         * @todo Если какая то сложная логика, то вынести в отдельный класс и подключить через DI
         * Функцию __ обернул бы в сервис перевода и подключал чернез DI. Так будет проще написать юнит тест.
         * К тому же мы будем иметь возможность подменить этот сервис в будущем не переписывая логику текущего класса
         */
        if ($dto->eventType === self::EVENT_TYPE_NEW_POSITION) {
            return __('NewPositionAdded', null, $dto->seller->id);
        } elseif ($dto->eventType === self::EVENT_TYPE_CHANGE_STATUS_POSITION && !empty($data['differences'])) {
            return __('PositionStatusHasChanged', [
                'FROM' => Status::getName($dto->fromStatus),
                'TO'   => Status::getName($dto->toStatus),
            ], $dto->seller->id);
        } else {
            throw new NotificationServiceBaseException('Unknown event type');
        }
    }

    /**
     * Билдит переменные шаблона
     *
     * @param NotificationAboutEventDto $dto
     * @return array
     * @throws NotificationServiceBaseException
     */
    protected function buildTemplateVariables(NotificationAboutEventDto $dto): array
    {
        $templateData = [
            'COMPLAINT_ID'       => $dto->complainId,
            'COMPLAINT_NUMBER'   => $dto->complainNumber,
            'CREATOR_ID'         => $dto->creator->id,
            'CREATOR_NAME'       => $dto->creator->getFullName(),
            'EXPERT_ID'          => $dto->expert->id,
            'EXPERT_NAME'        => $dto->expert->getFullName(),
            'CLIENT_ID'          => $dto->client->id,
            'CLIENT_NAME'        => $dto->client->name,
            'CONSUMPTION_ID'     => $dto->consumptionId,
            'CONSUMPTION_NUMBER' => $dto->consumptionNumber,
            'AGREEMENT_NUMBER'   => $dto->agreementNumber,
            'DATE'               => $dto->date->format('Y-m-d H:i:s'),
            'DIFFERENCES'        => $this->buildDifferences($dto),
        ];

        foreach ($templateData as $key => $tempData) {
            if (empty($tempData)) {
                throw new NotificationServiceBaseException("Template Data ({ $key }) is empty!");
            }
        }

        return $templateData;
    }

    protected function sendNotificationToEmployers(Seller $seller, string $emailFrom, array $templateVariables): bool
    {
        /**
         * @todo Использовать не глобальные функции а класс который предоставляет эмейлы
         * По той же причине что и описал выше. Тоже самое касается и MessageClient - подключить зависимость через DI
         */

        $emails = getEmailsByPermit($seller->id, 'tsGoodsReturn');

        $isSend = false;
        if (!empty($emailFrom) && count($emails) > 0) {
            foreach ($emails as $email) {
                MessagesClient::sendMessage(
                    [
                        [
                            'emailFrom' => $emailFrom,
                            'emailTo'   => $email,
                            'subject'   => __('complaintEmployeeEmailSubject', $templateVariables, $seller->id),
                            'message'   => __('complaintEmployeeEmailBody', $templateVariables, $seller->id),
                        ],
                    ],
                    $seller->id, NotificationEvents::CHANGE_RETURN_STATUS
                );
                $isSend = true;
            }
        }

        return $isSend;
    }

    protected function sendEmailNotificationNewStatusToClient(
        int $newStatus,
        Seller $seller,
        Contractor $client,
        string $emailFrom,
        array $templateVariables
    ): bool
    {
        if (!empty($client->email)) {
            MessagesClient::sendMessage(
                [
                    [
                        'emailFrom' => $emailFrom,
                        'emailTo'   => $client->email,
                        'subject'   => __('complaintClientEmailSubject', $templateVariables, $seller->id),
                        'message'   => __('complaintClientEmailBody', $templateVariables, $seller->id),
                    ],
                ],
                $seller->id,
                $client->id,
                NotificationEvents::CHANGE_RETURN_STATUS,
                $newStatus
            );
            return true;
        }

        return false;
    }

    protected function sendSmsNotificationNewStatusToClient(
        Seller $seller,
        Contractor $client,
        int $newStatus,
        array $templateVariables
    ): array
    {
        $error = '';
        //Я так понял переменная error передается по ссылке в метод send и туда записывается ошибка
        $isSend = (bool) NotificationManager::send(
            $seller->id,
            $client->id,
            NotificationEvents::CHANGE_RETURN_STATUS,
            $newStatus,
            $templateVariables,
            $error
        );

        return [
            'isSend' => $isSend,
            'errorMessage' => $error
        ];
    }

}