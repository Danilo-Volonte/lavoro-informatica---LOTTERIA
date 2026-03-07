<?php
session_start();

if (!isset($_SESSION['id_utente'])) {
    header("Location: login.php?msg=" . urlencode("Devi accedere per visualizzare il tuo profilo."));
    exit;
}

require_once 'connessione.php';

$id_utente = $_SESSION['id_utente'];
$utente = null;
$biglietti = [];
$messaggio_avatar = "";
$errore_avatar = "";

try {
    $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Gestione Upload Avatar
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['file_avatar'])) {
        $file = $_FILES['file_avatar'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $cartella_destinazione = 'avatars/';
            if (!file_exists($cartella_destinazione)) { mkdir($cartella_destinazione, 0777, true); }

            $estensione = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $estensioni_ammesse = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($estensione, $estensioni_ammesse)) {
                $nuovo_nome = 'avatar_' . $id_utente . '_' . time() . '.' . $estensione;
                $percorso_finale = $cartella_destinazione . $nuovo_nome;

                if (move_uploaded_file($file['tmp_name'], $percorso_finale)) {
                    $sql_update = "UPDATE utente SET avatar = :avatar WHERE id_utente = :id";
                    $stmt_up = $pdo->prepare($sql_update);
                    $stmt_up->execute([':avatar' => $percorso_finale, ':id' => $id_utente]);
                    $messaggio_avatar = "Avatar aggiornato con successo!";
                } else {
                    $errore_avatar = "Errore durante il salvataggio del file sul server.";
                }
            } else {
                $errore_avatar = "Formato non valido. Usa solo JPG, PNG o GIF.";
            }
        } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            $errore_avatar = "Errore durante l'upload del file.";
        }
    }

    // Recupero dati utente
    $sql_utente = "SELECT nickname, email, crediti, ruolo, data_nascita, avatar FROM utente WHERE id_utente = :id_utente";
    $stmt_utente = $pdo->prepare($sql_utente);
    $stmt_utente->execute([':id_utente' => $id_utente]);
    $utente = $stmt_utente->fetch(PDO::FETCH_ASSOC);

    // Recupero biglietti
    $sql_biglietti = "SELECT b.numero, l.nome, l.data_fine, l.aperta 
                      FROM biglietto b 
                      JOIN lotteria l ON b.id_lotteria = l.id_lotteria 
                      WHERE b.id_utente = :id_utente 
                      ORDER BY l.data_fine DESC";
    $stmt_biglietti = $pdo->prepare($sql_biglietti);
    $stmt_biglietti->execute([':id_utente' => $id_utente]);
    $biglietti = $stmt_biglietti->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Errore del database: " . $e->getMessage());
}

$avatar_path = !empty($utente['avatar']) && file_exists($utente['avatar']) ? $utente['avatar'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il Mio Profilo - Lotteria</title>
    <style>
        :root { --primary: #1e3a8a; --primary-hover: #1e40af; --accent: #f59e0b; --bg-color: #f8fafc; --card-bg: #ffffff; --text-main: #334155; --text-light: #64748b; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); }
        .header { background-color: var(--primary); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header h1 { margin: 0; font-size: 1.5rem; }
        .btn-logout { background-color: #ef4444; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background 0.3s; }
        .btn-logout:hover { background-color: #dc2626; }
        .btn-home { background-color: var(--card-bg); color: var(--primary); padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background 0.3s; margin-right: 10px;}
        .btn-home:hover { background-color: #e2e8f0; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 30px; }
        .profile-card { background-color: var(--card-bg); border-radius: 10px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 5px solid var(--primary); display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px; }
        .avatar-section { display: flex; flex-direction: column; align-items: center; gap: 10px; min-width: 150px; }
        .avatar-img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .upload-form { display: flex; flex-direction: column; gap: 5px; align-items: center; width: 100%;}
        .upload-form input[type="file"] { font-size: 0.75rem; max-width: 180px; }
        .btn-upload { background-color: var(--primary); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; width: 100%; }
        .btn-upload:hover { background-color: var(--primary-hover); }
        .user-details { flex-grow: 1; }
        .user-details h2 { margin: 0 0 10px 0; color: var(--primary); }
        .user-details p { margin: 5px 0; color: var(--text-light); }
        .user-details strong { color: var(--text-main); }
        .credits-box { background-color: #fffbeb; border: 2px solid var(--accent); border-radius: 10px; padding: 20px 30px; text-align: center; height: fit-content;}
        .credits-box .amount { display: block; font-size: 2.5rem; font-weight: bold; color: #d97706; }
        .credits-box .label { font-size: 1rem; color: #b45309; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;}
        .msg-success { background-color: #dcfce7; color: #166534; padding: 10px; border-radius: 5px; font-size: 0.9rem; margin-top: 10px; width: 100%; text-align: center;}
        .msg-error { background-color: #fee2e2; color: #991b1b; padding: 10px; border-radius: 5px; font-size: 0.9rem; margin-top: 10px; width: 100%; text-align: center;}
        .section-title { font-size: 1.3rem; color: var(--primary); border-bottom: 2px solid var(--accent); padding-bottom: 5px; margin-bottom: 20px; display: inline-block; font-weight: bold; }
        .tickets-table { width: 100%; border-collapse: collapse; background-color: var(--card-bg); border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .tickets-table th, .tickets-table td { padding: 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .tickets-table th { background-color: var(--primary); color: white; font-weight: 600; text-transform: uppercase; font-size: 0.9rem; }
        .tickets-table tr:hover { background-color: #f8fafc; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .status-open { background-color: #dcfce7; color: #166534; }
        .status-closed { background-color: #fee2e2; color: #991b1b; }
        .btn-create-lottery { background-color: var(--accent); color: white; border: none; padding: 12px 20px; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: background-color 0.3s; margin-top: 20px; }
        .btn-create-lottery:hover { background-color: #d97706; }
    </style>
</head>
<body>

    <header class="header">
        <h1>Area Personale</h1>
        <div>
            <a href="index.php" class="btn-home">Torna alla Home</a>
            <a href="logout.php" class="btn-logout">Esci</a>
        </div>
    </header>

    <div class="container">
        
        <div class="profile-card">
            <div class="avatar-section">
                <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Avatar Utente" class="avatar-img">
                <form action="profilo.php" method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="file" name="file_avatar" accept="image/png, image/jpeg, image/gif" required>
                    <button type="submit" class="btn-upload">Aggiorna Foto</button>
                </form>
                <?php if (!empty($messaggio_avatar)): ?><div class="msg-success"><?php echo $messaggio_avatar; ?></div><?php endif; ?>
                <?php if (!empty($errore_avatar)): ?><div class="msg-error"><?php echo $errore_avatar; ?></div><?php endif; ?>
            </div>

            <div class="user-details">
                <h2>Ciao, <?php echo htmlspecialchars($utente['nickname']); ?>!</h2>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($utente['email']); ?></p>
                <p><strong>Data di Nascita:</strong> <?php echo date('d/m/Y', strtotime($utente['data_nascita'])); ?></p>
                <p><strong>Ruolo:</strong> <?php echo strtoupper($utente['ruolo']); ?></p>
            </div>
            
            <div class="credits-box">
                <span class="amount"><?php echo htmlspecialchars($utente['crediti']); ?></span>
                <span class="label">Crediti</span>
            </div>
        </div>

        <div>
            <h3 class="section-title">I Miei Biglietti</h3>
            
            <?php if (count($biglietti) > 0): ?>
                <table class="tickets-table">
                    <thead>
                        <tr>
                            <th>Lotteria</th>
                            <th>Numero Biglietto</th>
                            <th>Data ed Ora Estrazione</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($biglietti as $b): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars(strtoupper($b['nome'])); ?></strong></td>
                                <td># <?php echo htmlspecialchars($b['numero']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($b['data_fine'])); ?></td>
                                <td>
                                    <?php if ($b['aperta']): ?>
                                        <span class="status-badge status-open">IN CORSO</span>
                                    <?php else: ?>
                                        <span class="status-badge status-closed">ESTRATTA</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-light); background: var(--card-bg); padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    Non hai ancora acquistato nessun biglietto. <a href="index.php" style="color: var(--primary); font-weight: bold;">Scopri le lotterie disponibili!</a>
                </p>
            <?php endif; ?>

            <form action="datiCreazioneLotteria.php" method="POST">
                <input name="idUtente" value=<?php echo $id_utente; ?> type="hidden">
                <button type="submit" class="btn-create-lottery">Crea Nuova Lotteria</button>
            </form>
        </div>
    </div>
</body>
</html>