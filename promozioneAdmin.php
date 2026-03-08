<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il Mio Profilo - Lotteria</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 350px;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        p {
            font-size: 16px;
            color: #555;
        }

        button {
            background: #4facfe;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.3s;
        }

        button:hover {
            background: #2f8cff;
            transform: scale(1.05);
        }

        form {
            margin-top: 20px;
        }
    </style>
</head>

<body>

</body>

</html>

<?php
if (isset($_POST['idUtente'])) {
    $idUtente = $_POST['idUtente'];


    require_once 'connessione.php';
    try {
        $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "update utente set ruolo = 'admin' where id_utente = :idUtente";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idUtente', $idUtente, PDO::PARAM_INT);
        $stmt->execute();
        echo "Utente promosso a admin con successo.";

    } catch (PDOException $e) {
        $errore = "Errore di connessione al database: " . $e->getMessage();
    }

} else {
    // Gestisci il caso in cui idUtente non è presente, ad esempio reindirizzando o mostrando un messaggio di errore
    echo "utente non specificato.";
}

echo "<form action = 'visualizzaListaUtenti.php'> <button type='submit'>Torna alla lista utenti</button> </form>";
?>