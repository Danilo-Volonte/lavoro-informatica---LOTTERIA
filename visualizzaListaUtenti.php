<?php
session_start();

// Controllo Sicurezza: solo gli admin possono vedere questa pagina!
if (!isset($_SESSION['id_utente']) || $_SESSION['ruolo'] !== 'admin') {
    header("Location: login.php?msg=" . urlencode("Accesso negato. Area riservata agli amministratori."));
    exit;
}

require_once 'connessione.php';

$utenti = [];
$errore_db = "";

try {
    $pdo = new PDO($conn_str, $conn_usr, $conn_psw, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Recuperiamo solo gli utenti standard
    $sql = "SELECT id_utente, nickname, crediti, email FROM utente WHERE ruolo = 'user'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $utenti = $stmt->fetchAll();

} catch (PDOException $e) {
    $errore_db = "Errore di connessione al database: " . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Utenti - Admin</title>
    <style>
        /* ====== PALETTE E STILI GLOBALI ====== */
        :root { --primary: #1e3a8a; --primary-hover: #1e40af; --accent: #f59e0b; --bg-color: #f8fafc; --card-bg: #ffffff; --text-main: #334155; --text-light: #64748b; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); }
        .header { background-color: var(--primary); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header h1 { margin: 0; font-size: 1.5rem; }
        .btn-home { background-color: var(--card-bg); color: var(--primary); padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background 0.3s; }
        .btn-home:hover { background-color: #e2e8f0; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .section-title { font-size: 1.5rem; color: var(--primary); border-bottom: 3px solid var(--accent); padding-bottom: 5px; margin-bottom: 20px; display: inline-block; font-weight: bold; }
        
        .table-container { background-color: var(--card-bg); border-radius: 10px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); border-top: 5px solid var(--primary); }
        .users-table { width: 100%; border-collapse: collapse; }
        .users-table th, .users-table td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        .users-table th { background-color: #f1f5f9; color: var(--text-main); font-weight: bold; text-transform: uppercase; font-size: 0.9rem; }
        .users-table tr:hover { background-color: #f8fafc; }
        .users-table tr:last-child td { border-bottom: none; }
        
        .crediti-badge { background-color: #fffbeb; color: #b45309; padding: 5px 10px; border-radius: 20px; font-weight: bold; border: 1px solid #fde68a; display: inline-block; }
        
        .btn-promote { background-color: #10b981; color: white; border: none; padding: 8px 12px; border-radius: 5px; font-size: 0.85rem; font-weight: bold; cursor: pointer; transition: background-color 0.2s; width: 100%; }
        .btn-promote:hover { background-color: #059669; }

        /* Stili per il nuovo form dei crediti */
        .crediti-form { display: flex; gap: 5px; }
        .input-crediti { width: 70px; padding: 6px; border: 1px solid #cbd5e1; border-radius: 5px; font-family: inherit; }
        .input-crediti:focus { outline: none; border-color: var(--primary); }
        .btn-add { background-color: var(--primary); color: white; border: none; padding: 6px 10px; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .btn-add:hover { background-color: var(--primary-hover); }

        .msg-errore { background-color: #fef2f2; color: #b91c1c; padding: 15px; border-radius: 6px; border-left: 4px solid #ef4444; font-weight: bold; margin-bottom: 20px; }
        .msg-successo { background-color: #dcfce7; color: #166534; padding: 15px; border-radius: 6px; border-left: 4px solid #10b981; font-weight: bold; margin-bottom: 20px; }
        .empty-msg { text-align: center; padding: 40px; color: var(--text-light); font-size: 1.1rem; }
    </style>
</head>

<body>
    <header class="header">
        <h1>Gestione Utenti</h1>
        <a href="profilo.php" class="btn-home">Torna al Profilo</a>
    </header>

    <div class="container">
        <h2 class="section-title">Lista Giocatori (User)</h2>
        
        <?php if (isset($_GET['successo'])): ?>
            <div class="msg-successo">✅ <?php echo htmlspecialchars($_GET['successo']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['errore'])): ?>
            <div class="msg-errore">❌ <?php echo htmlspecialchars($_GET['errore']); ?></div>
        <?php endif; ?>

        <?php if (!empty($errore_db)): ?>
            <div class="msg-errore"><?php echo $errore_db; ?></div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (empty($utenti) && empty($errore_db)): ?>
                <div class="empty-msg">Nessun utente con ruolo 'user' trovato nel sistema.</div>
            <?php elseif (!empty($utenti)): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Nickname</th>
                            <th>Email</th>
                            <th>Crediti Attuali</th>
                            <th>Aggiungi Crediti</th>
                            <th>Ruolo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utenti as $utente): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($utente['nickname']); ?></strong></td>
                                <td><?php echo htmlspecialchars($utente['email']); ?></td>
                                <td>
                                    <span class="crediti-badge">
                                        <?php echo number_format($utente['crediti'], 2, ',', '.'); ?> C
                                    </span>
                                </td>
                                
                                <td>
                                    <form action="aggiungiCrediti.php" method="POST" class="crediti-form">
                                        <input type="hidden" name="idUtente" value="<?php echo (int) $utente['id_utente']; ?>">
                                        <input type="number" name="importo" class="input-crediti" step="0.01" min="0.01" placeholder="Es. 10" required>
                                        <button type="submit" class="btn-add">+ Add</button>
                                    </form>
                                </td>

                                <td>
                                    <form action="promozioneAdmin.php" method="POST" style="margin: 0;" onsubmit="return confirm('Promuovere <?php echo htmlspecialchars($utente['nickname']); ?> ad Admin?');">
                                        <input type="hidden" name="idUtente" value="<?php echo (int) $utente['id_utente']; ?>">
                                        <button type="submit" class="btn-promote">↑ Promuovi</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>