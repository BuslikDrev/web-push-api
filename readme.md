# Push API - просто и понятно

**Обратите внимание!** Это руководство было написано в апреле 2016 года и часть информации могла устареть. Я постараюсь в ближайшее время актуализировать данные, но когда это произойдет точно - неизвестно.

Практически вся исчерпывающая информация была взята из руководства [Push Notifications on the Open Web](https://developers.google.com/web/updates/2015/03/push-notifications-on-the-open-web), документации [Push API](https://developer.mozilla.org/en-US/docs/Web/API/Push_API) и [Notifications API](https://developer.mozilla.org/en-US/docs/Web/API/Notifications_API). И, конечно, [Stack Overflow](http://stackoverflow.com/).

## Поддержка и требования

Для работы уведомлений требуется сервис-воркер и, соответственно работа сайта по HTTPS. Для тестирования на localhost сертификат не требуется. Поддерживают Push API на данный момент (март 2016) только Chrome 45 и Firefox 44, а так же Chrome 47 для Android, но в нем тестирования не производилось, так что описанное ниже справедливо лишь для десктопных Chrome 45, Яндекс.Браузер 16.3 и Firefox 44. Браузеры должны быть запущены для получения уведомлений, сайт при этом не обязательно дожен быть открыт. Если браузер закрыт, уведомления будут получены после его запуска, при условии соблюдения сроков хранения уведомлений (TTL). Firefox (44.0.2) при этом показывает уведомлении и когда закрыт.

- [Push API](http://caniuse.com/#feat=push-api)
- [Service Workers](http://caniuse.com/#feat=serviceworkers)

## Клиент

На клиенте нужно зарегистрировать сервис-воркер и подписаться на получение уведомлений от сайта. По [рекомендации Google](https://docs.google.com/document/d/1WNPIS_2F0eyDm5SS2E6LZ_75tk6XtBSnR1xNjWJ_DPE/edit#heading=h.55stxhevs7pi) запрос на получение уведомлений лучше делать по желанию пользователя, то есть, к примеру, соответствующей кнопкой на сайте, с поясняющим текстом что за уведомления и как часто они будут приходить. Это хорошая рекомендация, так как существует пугающая тенденция сразу запрашивать разрешение на получение уведомлений без объяснения, как часто они будут приходить и что из себя представляют. Не делайте так.

```html
<button class="js-push-button" disabled>
  Получать уведомления
</button>
```

Кнопка по умолчанию неактивна, а лучше её вообще скрыть с состоянием `disabled`, чтобы те пользователи, браузеры которых не поддерживают необходимые технологии, не видели предложение подписаться. В случае, если решите скрыть кнопку не забывайте о варианте со статусом `disabled` в случае отказа от уведомлений (см. схему проверки). В данном случае можно дать пояснения, как включить уведомления в зависимости от браузера.

Для начала необходимо зарегистрировать сервис-воркер. После успешной регистрации вызывается функция `initialiseState()`, описанная дальше.
```js
var isPushEnabled = false;

window.addEventListener('load', function() {
  var pushButton = document.querySelector('.js-push-button');
  pushButton.addEventListener('click', function() {
    if (isPushEnabled) {
      unsubscribe();
    } else {
      subscribe();
    }
  });

  // Проверяем поддержку Service Worker API 
  // и регистрируем наш сервис-воркер
  if ('serviceWorker' in navigator) {  
    navigator.serviceWorker.register('/service-worker.js')  
    .then(initialiseState);  
  }
});
```

На кнопку вешается обработчик события клика для подписки или её отмены. `isPushEnabled` - глобальная переменная для отслеживания и показа текущего состояния подписки.

### Начальное состояние

После регистрации сервис-воркера нужно определить состояние нашей кнопки.

Схема проверки поддержки и состояний такова:

![Схема проверки поддержки и состояний](https://github.com/eveness/web-push-api/blob/master/images/push_api_check_scheme.jpg)

Так как для работы требуется поддержка нескольких API, то состояние кнопки будет зависеть от их последовательной проверки.

```js
function initialiseState() {
  // Проверяем создание уведомлений при помощи Service Worker API
  if (!('showNotification' in ServiceWorkerRegistration.prototype)) {
    console.warn('Уведомления не поддерживаются браузером.');
    return;
  }

  // Проверяем не запретил ли пользователь прием уведомлений
  if (Notification.permission === 'denied') {  
    console.warn('Пользователь запретил прием уведомлений.');  
    return;  
  }

  // Проверяем поддержку Push API
  if (!('PushManager' in window)) {  
    console.warn('Push-сообщения не поддерживаются браузером.');  
    return;
  }

  // Проверяем зарегистрирован ли наш сервис-воркер
  navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {  
    // Проверяем наличие подписки  
    serviceWorkerRegistration.pushManager.getSubscription()
      .then(function(subscription) {  
        // Делаем нашу кнопку активной
        var pushButton = document.querySelector('.js-push-button');
        pushButton.disabled = false;

        if (!subscription) {  
          // Если пользователь не подписан
          return;
        }

        // Отсылаем серверу данные о подписчике
        sendSubscriptionToServer(subscription);

        // Меняем состояние кнопки
        pushButton.textContent = 'Отписаться от уведомлений';  
        isPushEnabled = true;  
      })  
      .catch(function(err) {  
        console.warn('Ошибка при получении данных о подписчике.', err);
      });
  });  
};
```

## Создание проекта в Google Developer Console

Уведомления в Chrome работают через GCM API, для доступа к которому нужно зарегистрировать проект в Google Developer Console. Регистрация и настройка простые ([то же самое со скриншотами](https://github.com/eveness/web-push-api/blob/master/create_project_in_google_developer_console_screens.md)):

- Заходим на https://console.developers.google.com
- Создаем новый проект `Create Project`
- Заходим в `Use Google APIs`
- В строке поиска вводим `Google Cloud Messaging`
- Переходим к `Google Cloud Messaging` и включаем его (сейчас он включен по умолчанию, но лучше убедиться)
- Переходим к `Credentials` -> `Create credentials` -> `API key` -> `Server key`
- Указываем `Name` и нажимаем `Create`
- Копируем себе полученное значение `API key`, оно нужно для отправки уведомлений
- Возвращаемся в Google Developer Console и копируем себе цифровое значение `Project number`, оно используется в качетстве параметра `gcm_sender_id` в манифесте сайта

Для работы уведомлений в Firefox дополнительных танцев с бубном не требуется.

## Создание манифеста сайта

Для использования уведомлений в Chrome через GCM, в файле манифеста нужно указать `gcm_sender_id` (`Project number` из Google Developer Console). Пример простого `manifest.json`:

```json
{
  "name": "Push API Demo",
  "short_name": "Push API Demo",
  "icons": [{
        "src": "/icon-192x192.png",
        "sizes": "192x192",
        "type": "image/png"
      }],
  "start_url": "/index.html?homescreen=1",
  "display": "standalone",
  "gcm_sender_id": "<Project number без #>"
}
```

Ссылку на файл манифеста указываем в `<head>`:

```html
<link rel="manifest" href="/manifest.json">
```

Если не указать `gcm_sender_id` в манифесте, то при попытке подписки возникнет ошибка `Registration failed - no sender id provided`.

## Подписка на уведомления

Для подписки вызывается метод `subscribe()` объекта `PushManager`, доступ к которому осуществляется через объект `ServiceWorkerRegistration`. В этот момент у пользователя запрашивается разрешение присылать уведомления (оповещения) с сайта. Внешний вид этого запроса зависит только от браузера и повлиять на него нельзя.

Метод `subscribe()` возвращает промис с объектом `PushSubscription`, который содержит свойство `endpoint` со ссылкой на подписчика.

```js
function subscribe() {
  // Блокируем кнопку на время запроса 
  // разрешения отправки уведомлений
  var pushButton = document.querySelector('.js-push-button');
  pushButton.disabled = true;

  navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {
    serviceWorkerRegistration.pushManager.subscribe({userVisibleOnly: true})
      .then(function(subscription) {
        // Подписка осуществлена
        isPushEnabled = true;
        pushButton.textContent = 'Отписаться от уведомлений';
        pushButton.disabled = false;

        // В этой функции необходимо регистрировать подписчиков
        // на стороне сервера, используя subscription.endpoint
        return sendSubscriptionToServer(subscription);
      })  
      .catch(function(err) {  
        if (Notification.permission === 'denied') {  
          // Если пользователь запретил присылать уведомления,
          // то изменить это он может лишь вручную 
          // в настройках браузера для сайта
          console.warn('Пользователь запретил присылать уведомления');
          pushButton.disabled = true;  
        } else {  
          // Отлавливаем другие возможные проблемы -
          // потеря связи, отсутствие gcm_sender_id и прочее
          console.error('Невожможно подписаться, ошибка: ', err);
          pushButton.disabled = false;
          pushButton.textContent = 'Получать уведомления';
        }  
      });  
  });  
};
```
## Получение уведомлений

Когда уведомление получено, сервис-воркер принимает событие `push`, на которое назначается обработчик. Пример ниже показывает статичное уведомление, вне зависимости от того, что было прислано. Забегая вперед - пока нельзя получить присланные данные в браузере, поэтому кастомизацию уведомлений нужно делать на нашем сервере.

```js
self.addEventListener('push', function(event) {
  console.log('Получено push-сообщение', event);

  var title = 'Ура, работает!';
  var body = 'Было получено сообщение от сайта.';
  var icon = '/icon-192x192.png';
  var tag = 'simple-push-demo-notification-tag';

  event.waitUntil( 
    self.registration.showNotification(title, { 
      body: body,
      icon: icon,
      tag: tag
    })
  );
});
```

Метод `waitUntil()` принимает промис и продлевает время жизни обработчика до тех пор, пока промис не завершится состоянием `settled`. В нашем случае это промис, возвращаемый методом `showNotification()`.

Параметр `tag`, принимаемый методом `showNotification()` является уникальным идентификатором уведомления. Если мы отправляем два уведомления одному подписчику с небольшой задержкой между ними, но с одинаковым значением `tag`, то браузер сначала покажет первое уведомление, а затем заменит его вторым. Если нужно, чтобы уведомления отображались один за другим, то используются разные значения `tag` или этот параметр вовсе не указывается.

## Регистрация подписчиков

При подписке на уведомления мы отправляем на наш сервер при помощи функции `sendSubscriptionToServer()` объект `PushSubscription` со свойством `endpoint`, которое содержит ссылку на сервер уведомлений с идентификатором подписчика. Для Chrome это ссылка `https://android.googleapis.com/gcm/send/<subscriber_id>`, для Firefox `https://updates.push.services.mozilla.com/push/<subscriber_id>`, где `<subscriber_id>` строка вида `eRhmy9_-Rx0:APA91bF6iLh_jiFTi840SaWx-Ndkrwa9M5OZ79wEiCtA1hxjulcNMPi3c0oYf_xdcOcMRuM18YKCSTuTgK7FU-zOzKfzR_RFcXhRWB837nLNuJeux8Go0TnjNX8w6zVMeBN0bhu-RmoA`, уникальная для каждого подписчика.

Так как способы отправки уведомлений для разных браузеров отличаются, на сервере соответственно нужно регистрировать как `<subscriber_id>`, так и браузер. Например, функция `sendSubscriptionToServer()` может быть такой:

```js
function sendSubscriptionToServer(subscription) {
  fetch(SERVER_API_SUBSCRIBERS, {
      method: 'post',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: 'url=' + subscription.endpoint
    })
    .then(function(response) {
      if (response.status !== 200) {
        // TODO: Оповещаем пользователя, что что-то пошло не так
        console.error('Хьюстон, у нас проблемы с регистрацией подписчиков: ' + response.status);
        return;
      }

      response.json().then(function(data) {
        // TODO: Оповещаем пользователя об успешной подписке
        console.log(data); 
      });
    })
    .catch(function(err) {
      // TODO: Оповещаем пользователя, что что-то пошло не так
      console.error('Хьюстон, у нас проблемы с регистрацией подписчиков: ', err);
    });
};
```
`SERVER_API_SUBSCRIBERS` соответственно ссылка на наш серверный API, который обрабатывает полученную информацию, разбирает строку `subscription.endpoint`, записывает нового подписчика или обновляет, если такой подписчик уже существует.

## Отправка уведомлений

Google Cloud Messaging (GCM) для Chrome принимает POST-запрос с заголовком `Authorization: key=` и телом с массивом `registration_ids` где указываются все или один подписчик.

Для Firefox PUT-запрос на `https://updates.push.services.mozilla.com/push/v5/` для каждого подписчика отправляется отдельно, массив отправить нельзя (`/v5/` причем было добавлено лишь недавно, ещё в марте 2016 работало без указания версии, так что нужно следить за ошибками при отправке). Так же обязательно требуется отправка заголовка `TTL: <seconds>`, где указывается время хранения уведомления, пока браузер пользователя закрыт. 

Пример решения отправки уведомлений для обоих браузеров на PHP был найден на [Stack Overflow](http://stackoverflow.com/a/35290785):

```php
function send_push_message($subscriptionIDs) {

  if (empty($subscriptionIDs)) return FALSE;
  $chs = $sChrome = array();
  $mh = curl_multi_init();
  foreach ($subscriptionIDs as $subscription) {
    $i = count($chs);
    switch ($subscription["browser"]) {
      case "firefox":
        $chs[ $i ] = curl_init();
        curl_setopt($chs[ $i ], CURLOPT_URL, "https://updates.push.services.mozilla.com/push/v5/".$subscription["id"] );
        curl_setopt($chs[ $i ], CURLOPT_PUT, TRUE);
        curl_setopt($chs[ $i ], CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($chs[ $i ], CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($chs[ $i ], CURLOPT_HTTPHEADER, array('TTL: TIME_TO_LIVE'));

        curl_multi_add_handle($mh, $chs[ $i ]);
        break;
      case "chrome":
        $sChrome[] = $subscription["id"];
        break;
    }
  }
  if (!empty($sChrome)) {
    $i = count($chs);
    $chs[ $i ] = curl_init();
    curl_setopt($chs[ $i ], CURLOPT_URL, "https://android.googleapis.com/gcm/send" );
    curl_setopt($chs[ $i ], CURLOPT_POST, TRUE);
    curl_setopt($chs[ $i ], CURLOPT_HTTPHEADER, array( "Authorization: key=MY_KEY", "Content-Type: application/json" ) );
    curl_setopt($chs[ $i ], CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($chs[ $i ], CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($chs[ $i ], CURLOPT_POSTFIELDS, json_encode( array( "registration_ids" => $sChrome, 'time_to_live' =>  TIME_TO_LIVE) ) );
    curl_multi_add_handle($mh, $chs[ $i ]);
  }

  do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
  } while ($running > 0);

  for ($i = 0; $i < count($chs); $i++) {
    curl_multi_remove_handle($mh, $chs[ $i ]);
  }

  curl_multi_close($mh);
}
```
`$subscriptionIDs` соответственно массив с ключами `id` и `browser`, собранный нами при помощи `sendSubscriptionToServer()`, `MY_KEY` - API key из проекта в Google Developer Console, `TIME_TO_LIVE` - время хранения уведомления на сервере, в секундах. У Chrome время хранения уведомления по умолчанию [4 недели](https://developers.google.com/cloud-messaging/concept-options#ttl), у Firefox по умолчанию уведомления не хранятся, нужно [обязательно указывать TTL в заголовке](https://blog.mozilla.org/services/2016/02/20/webpushs-new-requirement-ttl-header/). Максимальное значение для Firefox, по стандарту, равно [5184000 секундам](https://blog.mozilla.org/services/2016/02/20/webpushs-new-requirement-ttl-header/). Если указать 0, то уведомление будет показано только в случае доступности клиента (браузера подписчика).

В этом примере уведомления отправляются сразу всем подписчикам, что не позволит отследить возможные ошибки, такие как несуществующий подписчик. Целесообразно отправлять уведомления по одному и обрабатывать ответ для каждого подписчика. Но такой способ соответственно может оказаться дорогим, в зависимости от количества подписчиков.

### Ответ push-сервера

Push-сервер Firefox ничего не отвечает в случае успешной отправки, а если произошла ошибка, то она приходит в формате JSON и может быть вида:

```
{
  errno: 102,
  code: 400,
  error: "Bad Request"
}
```

GCM отвечает и в случае успешной отправки, и в случае ошибки, так же в JSON. Успешная отправка:

```
{
  multicast_id: 6705987818270255000,
  success: 1,
  failure: 0,
  canonical_ids: 0,
  results: [
    {
      message_id: "0:1457117049927983%3feb139b3feb139b"
    },
    {
      message_id: "0:1457117386881370%a43fa845a43fa845"
    }
  ]
}
```

Размер `results` соответственно зависит от количества подписчиков.

Если произошла ошибка, то ответ может быть таким:

```
{
  multicast_id: 9203816883844023000,
  success: 0,
  failure: 1,
  canonical_ids: 0,
  results: [
    {
      error: "NotRegistered"
    }
  ]
}
```

И комбо:

```
{
  multicast_id: 9203816883844023000,
  success: 1,
  failure: 1,
  canonical_ids: 0,
  results: [
    {
      message_id: "0:1457117049927983%3feb139b3feb139b"
    },
    {
      error: "NotRegistered"
    }
  ]
}
```

Коды ошибок и их описание: [Google](https://developers.google.com/cloud-messaging/http-server-ref#error-codes), [Firefox](http://autopush.readthedocs.org/en/latest/http.html#error-codes).

## Получение уведомлений с информацией с сервера

Наша задача кастомизировать уведомления, в отличии от примера выше. То есть дать пользователю понять, что именно произошло и куда ему идти. В данный момент получить в браузере текст сообщения и прочие данные с push-сервера невозможно, поэтому реализовывать показ уведомлений нужно на стороне нашего сервера.

Сервис-воркер для этой задачи будет работать несколько иначе:

```js
self.addEventListener('push', function(event) {
  // Так как пока невозможно передавать данные от push-сервера,
  // то информацию для уведомлений получаем с нашего сервера
  event.waitUntil(
    self.registration.pushManager.getSubscription().then(function(subscription) {
      fetch(SOME_API_ENDPOINT, {
        // В данном случае отправляются данные о подписчике, 
        // что позволит проверить или персонифицировать уведомление
        method: 'post',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: 'url=' + subscription.endpoint
      })
      .then(function(response) {
        if (response.status !== 200) {
          // TODO: Если сервер отдал неверные данные, 
          // нужно уведомить об этом пользователя или администратора
          console.log('Хьюстон, у нас проблемы с получением уведомлений: ' + response.status);
          throw new Error();
        }

        // Получаем ответ от сервера и проверяем его
        return response.json().then(function(data) { 
          if (data.error || !data.notification) { 
            console.error('Сервер вернул ошибку: ', data.error);
            throw new Error();  
          }  

          var title = data.notification.title;
          var message = data.notification.message;
          var icon = data.notification.icon;
          var notificationTag = data.notification.tag;
          var custom_data = data.notification.data;

          return self.registration.showNotification(title, {
            body: message,
            icon: icon,
            tag: notificationTag,
            data: custom_data
          });
        });
      })
      .catch(function(err) {
        // В случае ошибки отображаем уведомление
        // со статичными данными
        console.error('Невозможно получить данные с сервера: ', err);

        var title = 'Ошибочка вышла';
        var message = 'Мы хотели сообщить вам что-то важное, но у нас всё сломалось.';
        var icon = URL_TO_DEFAULT_ICON;
        var notificationTag = 'notification-error';
        return self.registration.showNotification(title, {
            body: message,
            icon: icon,
            tag: notificationTag
          });
      });
    })
  );  
});
```

Соответственно сервер по запросу на `SOME_API_ENDPOINT` должен отдавать валидный JSON вида:

```json
{
  "notification": {
    "title": "Заголовок уведомления",
    "message": "Текст уведомления",
    "icon": "<Путь к иконке>",
    "tag": "<Метка уведомления>",
    "data": "<Произвольные данные, в нашем случае ссылка>"
  }
}
```
Параметр `data` используется дальше для передачи ссылки перехода по клику на уведомление, так как предусмотренного для этого отдельного параметра нет. В `data` можно передавать любые данные, согласно спецификации, чем мы и воспользуемся.

## Переход по ссылке при клике на уведомление

Когда пользователь кликает по уведомлению (не на его закрытие или настройки), сервис-воркер получает событие `notificationclick`, к которому можно привязать фокусировку на окне или переход по ссылке.

```js
self.addEventListener('notificationclick', function(event) {
  console.log('Пользователь кликнул по уведомлению: ', event.notification.tag);
  // Закрываем уведомление
  event.notification.close();

  // Смотрим, открыта ли вкладка с данной ссылкой
  // и фокусируемся или открываем ссылку в новой вкладке
  event.waitUntil(
    clients.matchAll({
      type: 'window'
    })
    .then(function(clientList) {
      var url = event.notification.data;
      for (var i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if (client.url == url && 'focus' in client)
          return client.focus();
      }
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});
```

## Отмена подписки на уведомления

```js
function unsubscribe() {
  var pushButton = document.querySelector('.js-push-button');
  pushButton.disabled = true;

  navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {
    //  Для отмены подписки нужен объект subscription
    //  и его метод unsubscribe()
    serviceWorkerRegistration.pushManager.getSubscription().then(
      function(subscription) {
        // Проверяем есть ли подписка
        if (!subscription) {
          // Если нет, даем пользователю возможность
          // подписаться на уведомления
          isPushEnabled = false;
          pushButton.disabled = false;
          pushButton.textContent = 'Получать уведомления';
          return;  
        }  

        var endpoint = subscription.endpoint;
        // TODO: Отправить серверу данные о подписчике,
        // чтобы убрать его из списка рассылки

        subscription.unsubscribe().then(function(successful) { 
          pushButton.disabled = false;
          pushButton.textContent = 'Получать уведомления';
          isPushEnabled = false;
        }).catch(function(err) {
          // TODO: Если при отмене подписки возникла ошибка,
          // стоит как-то оповестить пользователя или админа

          console.log('Хьюстон, у нас проблемы с отменой подписки: ', err);
          pushButton.disabled = false;
          pushButton.textContent = 'Получать уведомления';
        });
      }).catch(function(err) { 
        console.error('Хьюстон, у нас проблемы с получением данных о подписчике: ', err);
      });
  });  
};
```

## Внешний вид уведомлений

Внешний вид уведомления, как и упоминалось ранее, зависит только от браузера и тут каждый из испытуемых выделяется. Слева направо: Яндекс.Браузер (16.3.0.6796), Google Chrome (49.0.2623.87), Mozilla Firefox (44.0.2):

![Примеры уведомлений в разных браузерах](https://github.com/eveness/web-push-api/blob/master/images/push_api_examples.jpg)

Ответ от сервера на `SOME_API_ENDPOINT` для этого уведомления был таким:
```
{
  "notification": {
    "title": "У вас новое сообщение!",
    "message": "19:00 от Push-Test\nНу и как работают эти уведомления?",
    "icon": "/icon-192x192.png",
    "data": "/?utm_source=push-api"
  }
}
```

Причем в Яндекс.Браузере был пойман не очень приятный момент. Если сайт на отличном от пользовательского языке, то в первую очередь предлагается перевод сайта, что перекрывает запрос на разрешение отправки уведомлений. И пока эта панель с предложением перевода не будет закрыта, пользователь так и не увидит запрос.

![Яндекс.Браузер](https://github.com/eveness/web-push-api/blob/master/images/yandex_browser_screen.jpg)