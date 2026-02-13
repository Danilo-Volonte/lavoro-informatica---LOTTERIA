<?php

$mail = $_POST['email'];
$password = $_POST['password'];

$pdo = new PDO("mysql:host=localhost;dbname=utenti", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "select password from utenti_registrati where mail = :email;";

$stm = $pdo->prepare($sql);

// Associazione parametri
$stm->bindParam(":email", $mail);
// Esecuzione della query
$stm->execute();
$passwordHashata = $stm->fetchAll(PDO::FETCH_ASSOC); //mette tutti i valori della tabella in un array associativo


if (password_verify($password, $passwordHashata[0]['password'])) {
    session_start();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "insert into utenti_loggati(mail_utente, data_ora) values (:mail,:ora);";
    $stm = $pdo->prepare($sql);

    $ora = date("Y-m-d H:i:s");

    // Associazione parametri
    $stm->bindParam(":mail", $mail);
    $stm->bindParam(":ora", $ora);

    // Esecuzione della query
    $stm->execute();

    echo "accesso riuscito <br> <form action='index.html'><button type='submit'>torna alla home</button></form>";
} else {
    echo "accesso negato <br> <form action='accedere.php'><button type='submit'>ritenta</button></form>";
}
?>