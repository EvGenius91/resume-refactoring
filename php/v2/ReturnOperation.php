<?php

namespace NW\WebService\References\Operations\Notification;

use DateTime;
use Exception;
use NW\WebService\References\Operations\Notification\contacts\dto\NotificationAboutEventDto;
use NW\WebService\References\Operations\Notification\contacts\INotificationService;
use NW\WebService\References\Operations\Notification\exceptions\InvalidRequestParameterException;
use NW\WebService\References\Operations\Notification\exceptions\ObjectNotFoundException;

/**
 * Этот класс обрабатывает запросы. Соответственно в нем не должно быть логики отправки, формирования сообщения и.т.д
 * он занимается только обработкой запроса и вызовом нужного сервиса
 */
class TsReturnOperation extends ReferencesOperation
{
    /**
     * @throws InvalidRequestParameterException
     */
    public function doOperation(): array
    {
        $data = (array)$this->getRequest('data');
        $dto = $this->buildDtoFromRequest($data);

        $notificationReport = $this->getNotificationService()->notificationAboutChangePosition($dto);

        return [
            'notificationEmployeeByEmail' => $notificationReport->notificationEmployeeByEmail,
            'notificationClientByEmail'   => $notificationReport->notificationClientByEmail,
            'notificationClientBySms'     => [
                'isSent'  => $notificationReport->notificationClientBySms,
                'message' => $notificationReport->notificationClientBySmsError ?? '',
            ]
        ];
    }

    /**
     * @throws InvalidRequestParameterException
     * @throws Exception
     */
    protected function buildDtoFromRequest(array $requestData): NotificationAboutEventDto
    {
        $requiredParameters = [
            'notificationType', 'resellerId', 'clientId', 'creatorId', 'expertId',
            'complaintId', 'complaintNumber', 'consumptionId', 'consumptionNumber',
            'agreementNumber', 'date'
        ];

        foreach ($requiredParameters as $parameter) {
            if (empty($requestData[$parameter])) {
                throw new InvalidRequestParameterException(
                    sprintf('Parameter %s must be defined', $parameter)
                );
            }
        }

        $dto = new NotificationAboutEventDto();
        $dto->complainId = $requestData['complainId'];
        $dto->complainNumber = $requestData['complainNumber'];
        $dto->consumptionId = $requestData['consumptionId'];
        $dto->consumptionNumber = $requestData['consumptionNumber'];
        $dto->agreementNumber = $requestData['agreementNumber'];
        $dto->eventType = $requestData['notificationType'];

        try {
            $dto->date = new DateTime($requestData['date']);
        } catch (Exception) {
            throw new InvalidRequestParameterException('Invalid date');
        }

        try {
            $dto->seller = Seller::getById((int) $requestData['resellerId']);
        } catch (ObjectNotFoundException) {
            throw new InvalidRequestParameterException('Seller not found');
        }

        try {
            $dto->client = Contractor::getById((int) $requestData['clientId']);
        } catch (ObjectNotFoundException) {
            throw new InvalidRequestParameterException('Client not found');
        }

        if ($dto->client->type !== Contractor::TYPE_CUSTOMER) {
            throw new InvalidRequestParameterException('Client is not customer');
        }

        if ($dto->client->Seller->id !== $dto->seller->id) {
            throw new InvalidRequestParameterException('Client not belongs to the seller');
        }

        try {
            $dto->creator = Employee::getById((int) $requestData['creatorId']);
        } catch (ObjectNotFoundException) {
            throw new InvalidRequestParameterException('Creator not found');
        }

        try {
            $dto->expert = Employee::getById((int) $requestData['expertId']);
        } catch (ObjectNotFoundException) {
            throw new InvalidRequestParameterException('Expert not found');
        }


        return $dto;
    }

    protected function getNotificationService(): INotificationService
    {
        //Здесь лучше подключать через сервис контейнер. Зависимости должны быть объявлены в одном месте
        return new NotificationService();
    }
}
