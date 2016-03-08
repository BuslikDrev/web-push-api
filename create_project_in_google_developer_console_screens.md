# Создание проекта в Google Developer Console для отправки push-сообщений

- Заходим на https://console.developers.google.com
- Создаем новый проект `Create Project`
- Заходим в `Use Google APIs`

![](https://github.com/eveness/web-push-api/blob/master/images/google_developer_console_01.jpg)

- В строке поиска вводим `Google Cloud Messaging`

![](https://github.com/eveness/web-push-api/blob/master/images/google_developer_console_02.jpg)

- Переходим к `Google Cloud Messaging` и включаем его (сейчас он включен по умолчанию, но лучше убедиться)

![](https://github.com/eveness/web-push-api/blob/master/images/google_developer_console_03.jpg)

- Переходим к `Credentials` -> `Create credentials` -> `API key` -> `Server key`

![](https://github.com/eveness/web-push-api/blob/master/images/google_developer_console_04.jpg)

![](https://github.com/eveness/web-push-api/blob/master/images/google_developer_console_05.jpg)

![](https://github.com/eveness/web-push-api/blob/master/images/google_developer_console_06.jpg)

- Указываем `Name` и нажимаем `Create`

![](https://github.com/eveness/web-push-api/blob/master/images/google_developer_console_07.jpg)

- Копируем себе полученное значение `API key`, оно нужно для отправки push-сообщений

![](https://github.com/eveness/web-push-api/blob/master/images/google_developer_console_08.jpg)

- Возвращаемся в Google Developer Console и копируем себе цифровое значение `Project number`, оно используется в качетсве параметра `gcm_sender_id` в манифесте сайта

![](https://github.com/eveness/web-push-api/blob/master/images/google_developer_console_09.jpg)