# События

События - это механизм, внедряющий элементы собственного кода в существующий код в определенные моменты его исполнения. 
К событию можно присоединить собственный код, который будет выполняться автоматически при срабатывании события. 
Например, объект, отвечающий за почту, может инициировать событие messageSent при успешной отправке сообщения. 
При этом если нужно отслеживать успешно отправленные сообщения, достаточно присоединить соответствующий код к событию 
messageSent.

## События в PHP

Для реализации механизма событий нужен:
1) Код события  - константа, например  EVENT_HELLO
2) Нужен обработчик события. Код который выполнится при срабатывании события. Обработчик нужно навесить 
   с помощью команды on
3) Событие нужно запустить с помощью метода trigger.


#### Для работы с событиями Yii использует базовый класс yii\base\Component. Если класс должен инициировать 
#### события, его нужно унаследовать от yii\base\Component или потомка этого класса.


## События в Yii2

Все прекреплённые обработчики хранятся в массиве $events класса Component.
Методы on и off хранятся в классе Component - он содержит работу с событиями и поведениями

1) В Yii2 используются события навешенные на ActiveRecord
такие как - ActiveRecord::EVENT_AFTER_INSERT
это событие срабатывают при вставке записи в БД автоматически.
Нет нужды заботиться о том чтобы его запустить.

В обработчике можно получить доступ к объекту, который инициировал событие, с помощью свойства $event->sender.

Реализация в контроллере:

    $article->on(ActiveRecord::EVENT_AFTER_INSERT, function ($event) {
            $followers = ['john2@teleworm.us',
                'shivawhite@cuvox.de',
                'kate@dayrep.com'
            ];

            foreach ($followers as $follower) {
                Yii::$app->mailer->compose()
                    ->setFrom('techblog@teleworm.us')
                    ->setTo($follower)
                    ->setSubject($event->sender->name)
                    ->setTextBody($event->sender->description)
                    ->send();
            }
            \yii\helpers\VarDumper::dump('Email sent successfuly!', 10, true);
        });
        if (!$article->save()) {
            echo VarDumper::dumpAsString($article->getErrors());
        };
        
Как видно здесь нет trigger. Событие срабатывает автоматически при вставки (сохранении) записи в БД 
в таблице article
 

По факту Если мы подвесим обработчик на ActiveRecord::EVENT_AFTER_INSERT
сработает метод afterSave() в классе BaseActiveRecord.

2) В любой модели мы можем имплементировать и видоизменить
метод beforeSave($insert) и afterSave($insert) 
их реализация находится в класса BAseActiveRecord


    public function beforeSave($insert)
        {
            $event = new ModelEvent();
            $this->trigger($insert ? self::EVENT_BEFORE_INSERT : self::EVENT_BEFORE_UPDATE, $event);

            return $event->isValid;
        }
        
        
которые запустят обработчик события автоматически.
но это касается модели.

Необходимо не забыть о том что нужен

parent::beforeSave($insert);

Иначе метод beforeSave внутри модели перезапишет всю реализацию .

#### Примечение метод beforeSave() должен вернуть true или false или parent::beforeSave($insert)
#### из метод afterSave() ничего возвращать не нужно.  

3) Так же в модели можно описать собственный обработчик 

    const EVENT_OUR_CUSTOM_EVENT = 'eventOurCustomEvent';

Но в этом случае обязательно его задействовать нужно:

    if ($article->save()) {
        $article->trigger(Article::EVENT_OUR_CUSTOM_EVENT);
    }
    
    
4) В LoginForm методе login() компонента \yii\web\User   
можно также навесить обработчик на событие 


    class User extends Component
    {
        const EVENT_BEFORE_LOGIN = 'beforeLogin';
        const EVENT_AFTER_LOGIN = 'afterLogin';
        const EVENT_BEFORE_LOGOUT = 'beforeLogout';
        const EVENT_AFTER_LOGOUT = 'afterLogout';

для этого в конфигурационном файле можно прописать:

    $config = [
       .........
        'components' => [

            'user' => [
                'identityClass' => 'app\models\User',
                'enableAutoLogin' => true,
                'on afterLogin' => function (\yii\web\UserEvent $event) {
                    $user = $event->identity;
                    $user -> updateAttributes(['logged_at' => time()]);
                },
            ],
       .........
       
здесь также можно вместо анонимной функции использовать статический метод класса
                
                namespase app\models;
                
                class User extends ActiveRecord {
                
                    public static function updateLastLogin (\yii\web\UserEvent $event) {
                                        $user = $event->identity;
                                        $user -> updateAttributes(['logged_at' => time()]);
                    },
                }
                
               
                
                'on afterLogin' => ['\app\models\User', 'updateLastLogin']

5) Навесить обработчик на событие Application в контроллере нельзя 
потому что приложение запускается раньше контроллера.
Для того чтобы навесить на приложение нужно навесить обработчик в конфигурационном файле или 
в index.php методом run


        abstract class Application extends Module
        {
            /**
             * @event Event an event raised before the application starts to handle a request.
             */
            const EVENT_BEFORE_REQUEST = 'beforeRequest';
            /**
             * @event Event an event raised after the application successfully handles a request 
             * (before the response is sent out).
             */
            const EVENT_AFTER_REQUEST = 'afterRequest';
        }
        
        

       $config = [
              .........
               'components' => [
     

               ],
               'on beforeRequest' => function ($event) {.....},
       ];
       
6) В классе Event добавлены статические методы on. off, trigger. Это сделано чтобы навешивать события по имени класса.
   Навесить событие на все экземпляры класса ActiveRecord.


    Event::on(ActiveRecord::class, ActiveRecord::EVENT_AFTER_INSERT, function ($event) {
        Yii::debug(get_class($event->sender) . ' добавлен');
    });       
    
Обработчик будет вызван при срабатывании события EVENT_AFTER_INSERT в экземплярах класса ActiveRecord или его потомков. 
В обработчике можно получить доступ к объекту, который инициировал событие, с помощью свойства $event->sender.
Т.е. после вставке записи в ЛЮБУЮ модель нажего сайта.

При срабатывании события будут в первую очередь вызваны обработчики на уровне экземпляра, 
а затем - обработчики на уровне класса.    


7) Сделаем систему оповещений для нашего итернет магазина. После вставки записи в таблицу-модель заказов
Order мы запустим обработчик orderCreated(), при вводе поситителем записи в поле оставить отзыв о товаре
т.е. в таблицу Opinion мы запустим обработчик opinionCreated().


    nsmespaxe app\components;
    
    use yii\base\BootstrapInterface;
    
    class ShopNotificator implements BootstrapInterface
    {
        public function bootstrap ($app)
        {
            Event::on(
                Order::className(),
                ActiveRacord::EVENT_AFTER_INSERT,
                [$this, 'orderCreated']
            );
            Event::on(
                Opinion::className(),
                ActiveRacord::EVENT_AFTER_INSERT,
                [$this, 'opinionCreated']
            );            
        }
        
        public function orderCreated(Event $event) {
            //Отправляем увежомление о заказе администратору и прадовцу
        }
        
        public function opinionCreated(Event $event) {
            //Отправляем увежомление об отзыве администратору и прадовцу
        }
    }
    
    
       $config = [
            'id' => 'app',
            'bootstrap' => [
                'log',
                '\app\components\ShopNotificator',
            ]
       ];
    