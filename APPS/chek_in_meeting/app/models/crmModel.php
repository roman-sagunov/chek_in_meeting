<?php
class CrmModel
{
    private $bitrix24;

    public function __construct()
    {
        // Загружаем данные для подключения к Bitrix24 из конфигурационного файла
        $bitrix24_config = require_once 'config/bitrix24.php';

        // Создаем объект Bitrix24 API с данными из конфигурационного файла
        $this->bitrix24 = new Bitrix24API(
            $bitrix24_config['domain'],
            $bitrix24_config['client_id'],
            $bitrix24_config['client_secret'],
            $bitrix24_config['access_token'],
            $bitrix24_config['refresh_token']
        );
    }

    // Здесь мы будем добавлять методы для работы с CRM и Встречами
	//преобразование формата в используемый Bitrix24
    private function getBitrix24EntityTypeId($crmEntityType) {
        switch ($crmEntityType) {
            case 'лид':
                return 1;
            case 'сделка':
                return 2;
            case 'контакт':
                return 3;
            case 'компания':
                return 4;
            default:
                return null;
        }
    }


	// Метод для получения информации о Встрече по её ID
	public function getMeetingById($meetingId)
	{
		// Отправляем запрос к Bitrix24 API для получения информации о Встрече
		$response = $this->bitrix24->callMethod('crm.activity.get', [
			'id' => $meetingId
		]);

		// Возвращаем результат запроса
		return $response['result'];
	}
	
	// Метод для псоздания встречи Встречи
	public function createMeetingForCrmEntity($crmEntityType, $crmEntityId, $userId) {
		$entityTypeId = $this->getBitrix24EntityTypeId($crmEntityType);

		if ($entityTypeId === null) {
			return false;
		}

		// Используем данные из файла конфигурации
		$bitrix24_config = require_once 'config/bitrix24.php';

		// Создаем новую встречу с текущей датой и временем и продолжительностью 1 час
		$response = $this->bitrix24->callMethod('crm.activity.add', [
			'fields' => [
				'TYPE_ID' => 2, // Тип сущности "Встреча"
				'SUBJECT' => "Встреча - " . date("d.m.Y"),
				'START_TIME' => date("Y-m-d H:i:s"),
				'END_TIME' => date("Y-m-d H:i:s", strtotime("+1 hour")),
				'COMPLETED' => 'N',
				'RESPONSIBLE_ID' => $userId,
				'BINDINGS' => [
				[
						'OWNER_ID' => $crmEntityId,
						'OWNER_TYPE_ID' => $entityTypeId
					]
				]
			]
		]);

		// Проверяем, была ли успешно создана встреча
		if (isset($response['result'])) {
			// Возвращаем идентификатор созданной встречи
			return $response['result'];
		} else {
			return false;
		}
	}

	// Метод для обновления координат местоположения Встречи
	public function updateMeetingLocation($meetingId, $latitude, $longitude)
	{
		// Отправляем запрос к Bitrix24 API для обновления координат местоположения Встречи
		$response = $this->bitrix24->callMethod('crm.activity.update', [
			'id' => $meetingId,
			'fields' => [
				'UF_LOCATION_LAT' => $latitude,
				'UF_LOCATION_LON' => $longitude
			]
		]);

		// Возвращаем результат запроса
		return $response['result'];
	}

	// Метод для обновления статуса Встречи (состоялась или нет)
	public function updateMeetingStatus($meetingId, $status)
	{
		// Отправляем запрос к Bitrix24 API для обновления статуса Встречи
		$response = $this->bitrix24->callMethod('crm.activity.update', [
			'id' => $meetingId,
			'fields' => [
				'UF_MEETING_STATUS' => $status
			]
		]);

		// Возвращаем результат запроса
		return $response['result'];
	}

	// Метод для получения информации о Лиде по ID
	public function getLeadById($leadId)
	{
		// Отправляем запрос к Bitrix24 API для получения информации о Лиде
		$response = $this->bitrix24->callMethod('crm.lead.get', [
			'id' => $leadId
		]);

		// Возвращаем результат запроса
		return $response['result'];
	}

	// Метод для получения информации о Сделке по ID
	public function getDealById($dealId)
	{
		// Отправляем запрос к Bitrix24 API для получения информации о Сделке
		$response = $this->bitrix24->callMethod('crm.deal.get', [
			'id' => $dealId
		]);

		// Возвращаем результат запроса
		return $response['result'];
	}

	// Метод для получения информации о Компании по ID
	public function getCompanyById($companyId)
	{
		// Отправляем запрос к Bitrix24 API для получения информации о Компании
		$response = $this->bitrix24->callMethod('crm.company.get', [
			'id' => $companyId
		]);

		// Возвращаем результат запроса
		return $response['result'];
	}

	// Метод для получения информации о Контакте по ID
	public function getContactById($contactId)
	{
		// Отправляем запрос к Bitrix24 API для получения информации о Контакте
		$response = $this->bitrix24->callMethod('crm.contact.get', [
			'id' => $contactId
		]);

		// Возвращаем результат запроса
		return $response['result'];
	}
	
	//получения информации о CRM-сущности, основываясь на ее типе
    public function getEntity($entityType, $entityId, $searchQuery) {
        $apiMethod = '';

        // Определение метода Bitrix24 API для получения информации о CRM-сущности
        switch ($entityType) {
            case 'LEAD':
                $apiMethod = 'crm.lead.get';
                break;
            case 'DEAL':
                $apiMethod = 'crm.deal.get';
                break;
            case 'CONTACT':
                $apiMethod = 'crm.contact.get';
                break;
            case 'COMPANY':
                $apiMethod = 'crm.company.get';
                break;
        }

        if (empty($apiMethod)) {
            return null;
        }

        // Запрос к Bitrix24 API для получения информации о CRM-сущности
        $apiResponse = $this->apiRequest($apiMethod, ['id' => $entityId]);

        if ($apiResponse['result']) {
            $entity = $apiResponse['result'];

            // Проверка соответствия CRM-сущности поисковому запросу
            // В данном случае, мы предполагаем, что у вас есть метод isEntityMatchingSearchQuery
            // который проверяет соответствие CRM-сущности поисковому запросу
            if ($this->isEntityMatchingSearchQuery($entity, $searchQuery)) {
                return $entity;
            }
        }

        return null;
    }
	
	//проверка соответствие CRM-сущности поисковому запросу
    public function isEntityMatchingSearchQuery($entity, $searchQuery) {
        // Проверка соответствия имени CRM-сущности поисковому запросу
        return mb_stripos($entity['TITLE'] ?? $entity['NAME'], $searchQuery) !== false;
    }

	// Метод для поиска встречи
	public function findMeetings($params) {
        // Создаем массив, в который будем записывать найденные встречи
        $meetings = [];

        // Запрос к Bitrix24 API для поиска встреч
        // В данном случае, мы предполагаем, что у вас есть метод apiRequest,
        // который осуществляет запросы к Bitrix24 API и возвращает результаты
        $apiResponse = $this->apiRequest('crm.meeting.list', [
            'filter' => [
                'RESPONSIBLE_ID' => $params['userId'],
                '>=DATE_START' => $params['dateFrom'],
                '<=DATE_START' => $params['dateTo'],
                'UF_MEETING_STATUS' => 'NOT_COMPLETED',
            ],
            'select' => ['ID', 'UF_*', 'ENTITY_TYPE', 'ENTITY_ID', 'DATE_START'],
            'order' => ['DATE_START' => 'ASC'],
            'limit' => 10,
        ]);

        // Обработка результатов запроса и формирование массива встреч
        if ($apiResponse['result']) {
            foreach ($apiResponse['result'] as $meeting) {
                // Проверка наличия привязанной CRM-сущности и соответствия ей поисковому запросу
                if (!empty($meeting['ENTITY_TYPE']) && !empty($meeting['ENTITY_ID'])) {
                    $entity = $this->getEntity($meeting['ENTITY_TYPE'], $meeting['ENTITY_ID'], $params['searchQuery']);

                    if ($entity) {
                        // Добавление встречи и связанной CRM-сущности в массив результатов
                        $meetings[] = [
                            'meeting' => $meeting,
                            'entity' => $entity,
                        ];
                    }
                }
            }
        }

        return $meetings;
    }
    
	// Метод для поиска сущности CRM
    public function findCrmEntities($params) {
        $userId = $params['userId'];
        $searchQuery = $params['searchQuery'];
        $entityType = $params['entityType'];

        // Здесь вам нужно реализовать запрос к вашей CRM-системе, чтобы получить сущности по переданным параметрам.
        // В качестве примера, ниже представлен примерный код для реализации такого запроса с использованием Bitrix24 API.

        $filter = [
            'ASSIGNED_BY_ID' => $userId,
        ];

        if (!empty($searchQuery)) {
            // Добавляем фильтр поиска по названию или другим полям в зависимости от типа сущности
            switch ($entityType) {
                case 'lead':
                case 'deal':
                case 'company':
                    $filter['%TITLE'] = $searchQuery;
                    break;
                case 'contact':
                    $filter['%FULL_NAME'] = $searchQuery;
                    break;
            }
        }

        $select = ['ID', 'TITLE', 'ASSIGNED_BY_ID', 'ASSIGNED_BY_NAME']; // Здесь вы можете указать поля, которые хотите получить
        $result = []; // Здесь будет результат запроса

        switch ($entityType) {
            case 'lead':
                // Здесь реализуйте запрос к Bitrix24 API для получения лидов
                $result = /* Запрос к Bitrix24 API */;
                break;
            case 'deal':
                // Здесь реализуйте запрос к Bitrix24 API для получения сделок
                $result = /* Запрос к Bitrix24 API */;
                break;
            case 'company':
                // Здесь реализуйте запрос к Bitrix24 API для получения компаний
                $result = /* Запрос к Bitrix24 API */;
                break;
            case 'contact':
                // Здесь реализуйте запрос к Bitrix24 API для получения контактов
                $result = /* Запрос к Bitrix24 API */;
                break;
        }

        return $result;
    }	
	// Здесь мы завершаем добавлять методы для работы с CRM и Встречами	
}
