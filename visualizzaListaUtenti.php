<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizzazione Lista Utenti - Lotteria</title>
    <link rel="stylesheet" href="stile.css">
</head>

<body>
    <header class="header">
        <h1>Lista Utenti</h1>
        <a href="profilo.php" class="btn">Torna al Profilo</a>
    </header>

    <div class="container">
        <h2 class="section-title">Utenti Registrati</h2>
        
    <?php
    require_once 'connessione.php';

    try {
        $pdo = new PDO($conn_str, $conn_usr, $conn_psw, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sql = "SELECT id_utente, nickname, crediti, email FROM utente WHERE ruolo = 'user'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $utenti = $stmt->fetchAll();

        if (!$utenti) {
            echo "<p>Nessun utente con ruolo 'user' trovato.</p>";
        } else {
            echo '<table class="users-table">';
            echo '<thead>
                <tr>
                    <th>Nickname</th>
                    <th>Crediti</th>
                    <th>Email</th>
                    <th>Azione</th>
                </tr>
              </thead>
              <tbody>';

            foreach ($utenti as $utente) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($utente['nickname']) . '</td>';
                echo '<td>' . htmlspecialchars($utente['crediti']) . '</td>';
                echo '<td>' . htmlspecialchars($utente['email']) . '</td>';
                echo '<td>
                    <form action="promozioneAdmin.php" method="POST">
                        <input type="hidden" name="idUtente" value="' . (int) $utente['id_utente'] . '">
                        <button type="submit" class="btn-submit">Promuovi a Admin</button>
                    </form>
                  </td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }
    } catch (PDOException $e) {
        echo "Errore di connessione al database: " . htmlspecialchars($e->getMessage());
    }
    ?>
        </div>
</body>

</html>