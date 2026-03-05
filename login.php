<?php
session_start();

// Se l'utente è già loggato, lo rimandiamo alla home
if (isset($_SESSION['id_utente'])) {
    header("Location: index.php");
    exit;
}

$errore = "";
// Catturiamo un eventuale messaggio passato tramite URL (es. da index.php)
$messaggio_info = isset($_GET['msg']) ? htmlspecialchars(trim($_GET['msg'])) : "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    require_once 'connessione.php';

    try {
        $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $sql = "SELECT id_utente, pw_hash, ruolo, nickname FROM utente WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $utente = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica hash
        if ($utente && password_verify($password, $utente['pw_hash'])) {
            $_SESSION['id_utente'] = $utente['id_utente'];
            $_SESSION['ruolo'] = $utente['ruolo'];
            $_SESSION['nickname'] = $utente['nickname'];

            header("Location: index.php");
            exit;
        } else {
            $errore = "Email o password errati.";
        }

    } catch (PDOException $e) {
        $errore = "Errore di connessione al database: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accedi - Lotteria</title>
    <style>
        /* ================= STESSA PALETTE DELLA HOME E REGISTRAZIONE ================= */
        :root {
            --primary: #1e3a8a;
            --primary-hover: #1e40af;
            --accent: #f59e0b;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #334155;
            --text-light: #64748b;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            padding: 20px; 
            box-sizing: border-box; 
        }
        
        .form-container { 
            background-color: var(--card-bg); 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            width: 100%; 
            max-width: 400px; 
            border-top: 5px solid var(--primary);
        }
        
        .form-container h2 { margin-top: 0; color: var(--primary); text-align: center; margin-bottom: 25px; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; color: var(--text-main); }
        .form-group input { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #cbd5e1; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1); }

        .btn-submit { 
            background-color: var(--primary); 
            color: white; 
            border: none; 
            padding: 12px; 
            width: 100%; 
            border-radius: 6px; 
            font-size: 1rem; 
            font-weight: bold; 
            cursor: pointer; 
            transition: background-color 0.3s; 
            margin-top: 10px;
        }
        .btn-submit:hover { background-color: var(--primary-hover); }

        /* Stili per i messaggi */
        .msg-errore { background-color: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-size: 0.9rem;}
        .msg-info { background-color: #eff6ff; color: #1d4ed8; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #3b82f6; font-size: 0.9rem; text-align: center; font-weight: bold;}

        /* Link inferiori */
        .links { margin-top: 25px; text-align: center; font-size: 0.9rem; display: flex; flex-direction: column; gap: 10px; }
        .links a { color: var(--primary); text-decoration: none; font-weight: 600; transition: color 0.2s;}
        .links a:hover { color: var(--accent); text-decoration: underline; }
        .links span { color: var(--text-light); font-weight: normal; }
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Accedi</h2>

        <?php if (!empty($messaggio_info)): ?>
            <div class="msg-info"><?php echo $messaggio_info; ?></div>
        <?php endif; ?>

        <?php if (!empty($errore)): ?>
            <div class="msg-errore"><?php echo $errore; ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Inserisci la tua email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Inserisci la password">
            </div>

            <button type="submit" class="btn-submit">Entra</button>
        </form>

        <div class="links">
            <a href="recupero_password.php">Hai dimenticato la password?</a>
            <div>
                <span>Non sei registrato?</span> 
                <a href="registrazione.php">Crea un account</a>
            </div>
            <a href="index.php" style="color: var(--text-light); font-weight: normal; margin-top: 10px; font-size: 0.8rem;">&larr; Torna alla Home</a>
        </div>
    </div>

</body>
</html>