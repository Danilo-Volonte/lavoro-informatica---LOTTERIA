<?php
$mail = $_POST['email'];

$passwordHashata = password_hash($_POST["password"], PASSWORD_DEFAULT);


$pdo = new PDO("mysql:host=localhost;dbname=utenti", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "INSERT INTO utente (mail, password) VALUES (:mail, :password);";

$stm = $pdo->prepare($sql);

// Associazione parametri
$stm->bindParam(":mail", $mail);
$stm->bindParam(":password", $passwordHashata);
// Esecuzione della query
$stm->execute();
echo "utente creato <br> <form action='index.html'><button type='submit'>torna alla home</button></form>";

?>