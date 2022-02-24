<?php


function is_sended(){
    if(isset($_POST['submit']) and $_POST['submit'] == 'Send'){
        if(isset($_FILES['received_file']) and $_FILES['received_file']['error'] == UPLOAD_ERR_OK){
            $file_tmp_path = $_FILES['received_file']['tmp_name'];
            $filename = $_FILES['received_file']['name'];
            $filename_parts = explode('.', $filename);
            $file_extension = strtolower(end($filename_parts));
    
            if($file_extension == 'csv'){
                $destination = './received_files/'.$filename;
                if(move_uploaded_file($file_tmp_path, $destination)){
                    return true;
                }
            }
        }
    }
    return false;
}


function array_from_csv($filename, $delimiter=','){
    if(!file_exists($filename)){
        return false;
    }

    $content = [];
    $row = '';
    $header = null;
    $handle = fopen($filename, 'r');

    if(!$handle){
        return false;
    }

    while (($row = fgetcsv($handle, 1000, $delimiter))){
            if(!$header)
                $header = $row;
            else
                $content[] = array_combine($header, $row);
    }
    fclose($handle);
    return $content;
}


function csv_from_array_download($array, $filename = "result.csv", $delimiter=",") {
    header('Content-Type: application/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'";');

    $f = fopen('php://output', 'w');
    $titles = ['Код', 'Название', 'Errors'];

    fputcsv($f, $titles, $delimiter);

    foreach ($array as $line) {
        fputcsv($f, $line, $delimiter);
    }
}   


function connect_to_db($db_config){
    $host = $db_config['host'];
    $dbname = $db_config['dbname'];
    $username = $db_config['username'];
    $password = $db_config['password'];

    try{
        $db = new PDO("mysql:host={$host};dbname={$dbname};charset=UTF8", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch(PDOException $e){
        echo 'An error occurs when connecting to DataBase: '.$e->getMessage();
        exit();
    }
}


function create_db_table($db, $table){
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id INT PRIMARY KEY AUTO_INCREMENT,
        code INT UNIQUE NOT NULL,
        title VARCHAR(255) NOT NULL,
        CONSTRAINT {$table}_check_title CHECK (title REGEXP
        '^[а-яА-ЯёЁa-zA-Z0-9\.-]+$'
        )
        )";
    $db->exec($sql);
}


function insert_into_db($db, $data, &$errors, $table){
    $query = "INSERT INTO {$table} VALUES (NULL, ?, ?)";
    $stmt = $db->prepare($query);

        foreach($data as $row){
            $db->beginTransaction();
            try{
                $stmt->execute(array($row['Код'], $row['Название']));
                $db->commit();
            } catch(PDOException $e){
                    $db->rollBack();
                    if($e->getCode() == 23000){
                        continue;
                    }
                    preg_match('/[^а-яА-ЯёЁa-zA-Z0-9\.-]+/', $row['Название'], $matches);
                    $sym = join($matches);
                    $errors[] = "Недопустимый символ \"{$sym}\" в поле {$row['Название']}";
            }
        }
}

function update_db($db, $data, $table){
    $query = "UPDATE {$table} SET title=? WHERE code=?";
    $stmt = $db->prepare($query);

    foreach($data as $row){
        $db->beginTransaction();
        try{
            $stmt->execute(array($row['Название'], $row['Код']));
            $db->commit();
        } catch(PDOException $e){
            $db->rollBack();
        }
    }
}

function prepare_data_to_csv($q, $errors){
    $res = [];
    for($i = 0, $j = 0; $i < count($q); $i++, $j++){
        if(isset($errors[$j])){
            $tmp = array_merge($q[$i], [$errors[$j]]);
        } else{
            $tmp = $q[$i];
        }
        $res[] = $tmp;
    }
    return $res;
}

if(is_sended()){
    $path = 'received_files/'.$_FILES['received_file']['name'];

    $data = array_from_csv($path);
    $errors = [];

    $db_config = [
        'host' => 'localhost',
        'dbname' => 'columbus_junior',
        'username' => 'user',
        'password' => 'pass',
    ];
    $tabname = 'catalog';
    $db = connect_to_db($db_config);
    create_db_table($db, $tabname);
    insert_into_db($db, $data, $errors, $tabname);
    update_db($db, $data, $tabname);

    $sql = "SELECT code, title FROM {$tabname}";
    $q = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $res = prepare_data_to_csv($q, $errors);
    csv_from_array_download($res);

}


?>
