# Тестовое задание
---
1. Общий принцип работы скрипта
2. Функции  
  2.1 is_sended()<br />
  2.2 array_from_csv()<br />
  2.3 Функции для работы с БД<br />
  2.4 prepare_data_to_csv()<br />
  2.5 csv_from_array_download() <br />
3. Код для создания таблицы в MySQL
4. Примеры работы скрипта можно увидеть в директории ./samples/
---
1. Общий принцип работы скрипта<br />
  Файл в формате .csv загружается в форму, расположенную в файле index.html.<br />
  Производится проверка на успешность операции отправки файла.<br />
  Если файл был успешно принят сервером, то он переносится из временной директории в директорию ./received_files/<br />
  Далее данные из файла считываются в двумерный массив $data, где каждый его элемент содержит данные из одной строки принятого .csv файла.<br />
  Затем создается подключение к базе данных на основе PDO.<br />
  Создается таблица с названием $table.<br />
  Далее происходит вставка всех элементов массива $data в созданную таблицу.<br />
  Затем происходит обновление всех элементов в созданной таблице.<br />
  (Т.е. вставка и обновление элементов разделены, поэтому сначала вставляются все новые элементы в таблицу, собираются ошибки, затем происходит обновление уже существующих элементов в таблице. Получается полный перебор всех строк .csv файла дважды + отработка функции update_db() вхолостую, когда таблица была только создана :(( ).<br />
  После этого происходит выборка ячеек таблицы из колонок code и title, подготовка данных к записи в .csv файл и отправка нового файла пользователю.<br />
  ---
  2. Функции<br />
  2.1 is_sended() <br />
  Проверяется была ли отправлена форма методом POST, затем получается временная инфа о переданном файле(путь, название, расширение и тп), если файл оказывается нужного расширения, то он переносится в директорию ./received_files/, возвращается true, иначе - false.<br />
  2.2 array_from_csv() <br />
  Возвращается массив с данными, полученными из .csv файла. Каждому эл-ту результирующего массива соответствует строка исходного файла.
  2.3 connect_to_db() - подключение к бд, create_db_table() - создание таблицы в бд, insert_into_db() - вставка в бд, update_db() - обновление ячеек бд.<br />
  2.4 prepare_data_to_csv()<br />
  Соединие массива с ошибками и массива данных, полученных из таблицы в бд. Данная функция создана для более удобной записи данных в .csv файл.<br />
  2.5.csv_from_array_download() <br />
  Создание файла .csv из массива данных и ошибок и автоматическая загрузка после создания.<br />
  
  3. Код для создания таблицы в БД.<br />
  CREATE TABLE IF NOT EXISTS {$table} (<br />
        id INT PRIMARY KEY AUTO_INCREMENT,<br />
        code INT UNIQUE NOT NULL,<br />
        title VARCHAR(255) NOT NULL,<br />
        CONSTRAINT {$table}_check_title CHECK (title REGEXP<br />
        '^[а-яА-ЯёЁa-zA-Z0-9\.-]+$'))
 ---
