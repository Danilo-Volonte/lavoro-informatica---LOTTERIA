<?php
session_start();

// Sicurezza: Solo un admin loggato può eseguire questa pagina
if (!isset($_SESSION['id_utente']) || $_SESSION['ruolo'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'connessione.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['idUtente']) && isset($_POST['importo'])) {
    $idUtente = (int) $_POST['idUtente'];
    $importo = (float) $_POST['importo']; // Usiamo float per permettere i decimali

    // Controlliamo che l'admin non stia cercando di inserire numeri negativi
    if ($importo <= 0) {
        header("Location: visualizzaListaUtenti.php?errore=" . urlencode("L'importo deve essere maggiore di zero."));
        exit;
    }

    try {
        $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Aggiungiamo i crediti al saldo attuale dell'utente
        $sql = "UPDATE utente SET crediti = crediti + :importo WHERE id_utente = :idUtente";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':importo' => $importo,
            ':idUtente' => $idUtente
        ]);

        header("Location: visualizzaListaUtenti.php?successo=" . urlencode("Aggiunti $importo crediti all'utente!"));
        exit;

    } catch (PDOException $e) {
        header("Location: visualizzaListaUtenti.php?errore=" . urlencode("Errore di connessione: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: visualizzaListaUtenti.php");
    exit;
}
?>