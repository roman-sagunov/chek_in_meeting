<?php
require_once 'crmModel.php';

class ChatbotModel
{
    private $crmModel;

    public function __construct()
    {
        // Создаем объект CrmModel для доступа к функционалу CRM и Встреч
        $this->crmModel = new CrmModel();
    }

    // Здесь мы будем добавлять методы для обработки команд чат-бота и генерации ответов

	// обработка команд
	public function processCommand($command, $parameters)
	{
    switch ($command) {
        case '/встреча':
            return $this->processMeetingCommand($parameters);
        case '/лид':
            return $this->processLeadCommand($parameters);
        case '/сделка':
            return $this->processDealCommand($parameters);
        case '/компания':
            return $this->processCompanyCommand($parameters);
        case '/контакт':
            return $this->processContactCommand($parameters);
        default:
            return 'Неизвестная команда.';
    }
}
	// Обработка команды "/встреча" и генерация ответа
	public function processMeetingCommand($userId, $commandText)
    {
        // Разбиваем текст команды на части, разделенные запятыми
        $parts = explode(',', $commandText);

        // Инициализируем переменные для хранения модификаторов
        $dateModifier = '';
        $entityModifier = '';
        $searchQuery = '';

        // Обходим все части текста команды и ищем модификаторы
        foreach ($parts as $part) {
            $part = trim($part);

            // Ищем модификатор даты
            if (in_array($part, ['сегодня', 'завтра', 'вчера', 'неделя назад', 'неделя вперед'])) {
                $dateModifier = $part;
            }

            // Ищем модификатор привязки встречи к сущности и поисковый запрос
            if (preg_match('/^(лид|сделка|компания|контакт) - (.+)$/', $part, $matches)) {
                $entityModifier = $matches[1];
                $searchQuery = $matches[2];
            }
        }

        // Проверяем, что хотя бы один модификатор был найден
        if (empty($dateModifier) && empty($entityModifier)) {
            return 'Не указаны модификаторы для команды /встреча. Пожалуйста, попробуйте снова с корректными модификаторами.';
        }

        // Используем метод класса CrmModel для поиска подходящих встреч
        $meetings = $this->crmModel->findMeetings($userId, $dateModifier, $entityModifier, $searchQuery);

        // Формируем ответ
        $response = '';
        $counter = 0;
        foreach ($meetings as $meeting) {
            $counter++;
            if ($counter > 10) {
                break;
            }
            $response .= $this->formatMeetingResult($meeting);
        }

        // Создаем экземпляр класса CrmModel и вызываем метод findMeetings с переданными параметрами
        $crmModel = new CrmModel();
        $meetings = $crmModel->findMeetings([
            'userId' => $userId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'searchQuery' => $searchQuery,
        ]);
        return $response;
    }
	// Обработка команды "/лид" и генерация ответа
    public function processLeadCommand($userId, $commandText) 
	{
        return $this->processCrmEntityCommand($userId, $commandText, 'lead');
    }
	// Обработка команды "/сделка" и генерация ответа
    public function processDealCommand($userId, $commandText) 
	{
        return $this->processCrmEntityCommand($userId, $commandText, 'deal');
    }
	// Обработка команды "/компания" и генерация ответа
    public function processCompanyCommand($userId, $commandText) 
	{
        return $this->processCrmEntityCommand($userId, $commandText, 'company');
    }
	// Обработка команды "/контакт" и генерация ответа
    public function processContactCommand($userId, $commandText) 
	{
        return $this->processCrmEntityCommand($userId, $commandText, 'contact');
    }

    private function processCrmEntityCommand($userId, $commandText, $entityType) 
	{
        // Убираем из текста команды имя сущности и символ "-"
        $searchQuery = trim(str_replace("/$entityType -", '', $commandText));

        // Создаем экземпляр класса CrmModel и вызываем метод findCrmEntities с переданными параметрами
        $crmModel = new CrmModel();
        $entities = $crmModel->findCrmEntities([
            'userId' => $userId,
            'searchQuery' => $searchQuery,
            'entityType' => $entityType,
        ]);

        // Формируем ответ для пользователя
        $response = [];
        foreach ($entities as $entity) {
            $response[] = [
                'text' => $this->formatCrmEntityResult($entity, $entityType),
                'buttons' => [
                    'Показать встречи',
                    'Создать встречу'
                ]
            ];
        }

        return $response;
    }
    // Форматирует результат CRM-сущности для отображения пользователю
    private function formatCrmEntityResult($entity, $entityType) {
        // Здесь вы можете настроить форматирование текста для каждой CRM-сущности.
        $text = '';
        switch ($entityType) {
            case 'lead':
                $text = "Лид - {$entity['TITLE']}\nОтветственный - {$entity['ASSIGNED_BY_NAME']}";
                break;
            case 'deal':
                $text = "Сделка - {$entity['TITLE']}\nОтветственный - {$entity['ASSIGNED_BY_NAME']}";
                break;
            case 'company':
                $text = "Компания - {$entity['TITLE']}\nОтветственный - {$entity['ASSIGNED_BY_NAME']}";
                break;
            case 'contact':
                $text = "Контакт - {$entity['FULL_NAME']}\nОтветственный - {$entity['ASSIGNED_BY_NAME']}";
                break;
        }

        return $text;
    }

// Обработка кнопок
public function processButtonClick($buttonName, $parameters)
{
    switch ($buttonName) {
        case 'Chek in':
            return $this->processCheckInButtonClick($parameters);
        case 'Показать встречи':
            return $this->processShowMeetingsButtonClick($parameters);
        case 'Создать встречу':
            return $this->processCreateMeetingButtonClick($parameters);
        default:
            return 'Неизвестное действие кнопки.';
    }
}
	// Обработка нажатия кнопки "Chek in"
    public function processCheckInButtonClick($userId, $meetingId) 
	{
        // Проверяем существование встречи с заданным ID
        $crmModel = new CrmModel();
        $meeting = $crmModel->getMeetingById($meetingId);
        if (!$meeting) {
            return [
                'error' => true,
                'message' => 'Встреча не найдена. Проверьте правильность введенного ID встречи.'
            ];
        }

        // Проверяем, является ли пользователь ответственным за данную встречу
        if ($meeting['RESPONSIBLE_ID'] != $userId) {
            return [
                'error' => true,
                'message' => 'Вы не являетесь ответственным за данную встречу. Не удалось выполнить действие.'
            ];
        }

        // Замените полный URL на относительный путь до вашей внешней страницы
        $externalPageUrl = "/chek_in_meeting/public/index.php?meeting_id={$meetingId}&user_id={$userId}";

        return [
            'error' => false,
            'externalPageUrl' => $externalPageUrl
        ];
    }
	// Обработка нажатия кнопки "Показать встречи"
    public function processShowMeetingsButtonClick($userId, $entityType, $entityId) {
        $crmModel = new CrmModel();
        
        // Получаем список незавершенных встреч для данной CRM-сущности, где пользователь является ответственным
        $meetings = $crmModel->getMeetingsByEntity($userId, $entityType, $entityId);
        
        // Форматируем результаты для отправки пользователю
        $formattedMeetings = [];
        foreach ($meetings as $meeting) {
            $formattedMeetings[] = $this->formatMeetingForDisplay($meeting);
        }

        return $formattedMeetings;
    }
	private function formatMeetingForDisplay($meeting) {
		// Здесь мы форматируем информацию о встрече для отображения пользователю
		// Таким образом, каждая встреча будет представлена в виде текста с кнопкой "Chek in"

		$entityType = $meeting['ENTITY_TYPE'];
		$entityTitle = $meeting['ENTITY_TITLE'];
		$meetingDate = date('d.m.Y H:i', strtotime($meeting['DATE']));

		$formattedMeeting = "Тип сущности: {$entityType}\n" .
                        "Название: {$entityTitle}\n" .
                        "Дата и время встречи: {$meetingDate}\n\n";
                        
		// Добавляем кнопку "Chek in"
		$formattedMeeting .= "<a href='chek_in_meeting/public/index.php/checkin?meeting_id={$meeting['ID']}&user_id={$meeting['RESPONSIBLE_ID']}'>Chek in</a>";

		return $formattedMeeting;
	}
	// Обработка нажатия кнопки "Создать встречу"
	public function processCreateMeetingButtonClick($userId, $crmEntityId, $crmEntityType) {
		// Создаем новую встречу, привязанную к указанной CRM-сущности
		$crmModel = new CrmModel();
		$createdMeeting = $crmModel->createMeetingForCrmEntity($userId, $crmEntityId, $crmEntityType);

		if ($createdMeeting) {
			// Выводим информацию о созданной встрече с кнопкой "Chek in"
			$formattedMeeting = $this->formatMeetingForDisplay($createdMeeting);
			return "Новая встреча создана:\n\n" . $formattedMeeting;
		} else {
			return "Не удалось создать встречу. Пожалуйста, попробуйте еще раз.";
		}
	}

// Методы для работы с внешней web страницей
public function sendMeetingDataToExternalPage($meetingId)
{
    // Получаем данные о встрече из CRM
    $meetingData = $this->crmModel->getMeetingById($meetingId);

    // Отправляем данные о встрече на внешнюю страницу
    // Здесь будет код отправки данных на внешнюю страницу, например, через POST-запрос или WebSocket
}

	
	// Здесь мы завершаем добавлять методы для обработки команд чат-бота и генерации ответов
}
