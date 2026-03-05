<?php
// 1. Avvia o recupera la sessione corrente
session_start();

// 2. Svuota tutte le variabili di sessione (cancella id_utente, ruolo, ecc.)
$_SESSION = array();

// 3. Se si usa un cookie di sessione (comportamento standard di PHP), eliminalo
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Distrugge definitivamente la sessione sul server
session_destroy();

// 5. Reindirizza l'utente alla Home Page (o al login) con un piccolo parametro di conferma
header("Location: login.php?msg=" . urlencode("Logout effettuato con successo. A presto!"));
exit;
?>