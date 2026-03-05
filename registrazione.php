<?php
session_start();
$errore = "";
$successo = "";

// Funzione helper per validare la password
function validaPassword($pwd) {
    // Requisiti:
    // Almeno 8 caratteri
    // Almeno una lettera maiuscola (?=.*[A-Z])
    // Almeno una lettera minuscola (?=.*[a-z])
    // Almeno un numero (?=.*\d)
    // Almeno un carattere speciale (?=.*[\W_]) -> \W significa "non alfanumerico", _ include l'underscore
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    return preg_match($pattern, $pwd);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'connessione.php';

    try {
        $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $conferma_password = $_POST['conferma_password'];
        $nickname = trim($_POST['nickname']);
        $data_nascita = $_POST['data_nascita'];
        $ruolo = $_POST['ruolo'];

        // 1. Controllo coincidenza password
        if ($password !== $conferma_password) {
            $errore = "Le password non coincidono!";
        } 
        // 2. Controllo complessità password
        elseif (!validaPassword($password)) {
            $errore = "La password deve contenere almeno 8 caratteri, una maiuscola, una minuscola, un numero e un carattere speciale.";
        } 
        else {
            // Hashing della password per sicurezza
            $pw_hash = password_hash($password, PASSWORD_DEFAULT);

            // Inserimento: crediti prenderà il DEFAULT 100 dal database 
            $sql = "INSERT INTO utente (email, pw_hash, nickname, data_nascita, ruolo) 
                    VALUES (:email, :pw_hash, :nickname, :data_nascita, :ruolo)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':email' => $email,
                ':pw_hash' => $pw_hash,
                ':nickname' => $nickname,
                ':data_nascita' => $data_nascita,
                ':ruolo' => $ruolo
            ]);

            $successo = "Registrazione completata! Ti sono stati accreditati 100 crediti bonus. <br><br> <a href='login.php' class='btn-submit' style='display:inline-block; text-decoration:none;'>Vai al Login</a>";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $errore = "Questa email è già in uso. Scegline un'altra.";
        } else {
            $errore = "Errore: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - Lotteria</title>
    <style>
        /* ================= STESSA PALETTE DELLA HOME ================= */
        :root {
            --primary: #1e3a8a;
            --primary-hover: #1e40af;
            --accent: #f59e0b;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #334155;
            --text-light: #64748b;
        }

        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; box-sizing: border-box; }
        
        .form-container { 
            background-color: var(--card-bg); 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            width: 100%; 
            max-width: 450px; 
            border-top: 5px solid var(--primary);
        }
        
        .form-container h2 { margin-top: 0; color: var(--primary); text-align: center; margin-bottom: 25px; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; color: var(--text-main); }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #cbd5e1; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1); }

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
            text-align: center;
        }
        .btn-submit:hover { background-color: var(--primary-hover); }

        .msg-errore { background-color: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-size: 0.9rem;}
        .msg-successo { background-color: #f0fdf4; color: #15803d; padding: 20px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #22c55e; text-align: center; line-height: 1.5;}

        .link-back { display: block; text-align: center; margin-top: 20px; color: var(--text-light); text-decoration: none; font-size: 0.9rem; }
        .link-back:hover { color: var(--primary); text-decoration: underline; }
        
        /* Aggiunto stile per la nota esplicativa della password */
        .password-hint { font-size: 0.75rem; color: var(--text-light); margin-top: 4px; display: block; }
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Crea un Account</h2>

        <?php if (!empty($errore)): ?>
            <div class="msg-errore"><?php echo $errore; ?></div>
        <?php endif; ?>

        <?php if (!empty($successo)): ?>
            <div class="msg-successo"><?php echo $successo; ?></div>
        <?php else: ?>
            <form method="POST" action="registrazione.php">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required> 
                </div>

                <div class="form-group">
                    <label for="nickname">Nickname</label>
                    <input type="text" id="nickname" name="nickname" maxlength="30" required>
                </div>

                <div class="form-group">
                    <label for="data_nascita">Data di Nascita</label>
                    <input type="date" id="data_nascita" name="data_nascita" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <span class="password-hint">Min. 8 caratteri, 1 maiuscola, 1 minuscola, 1 numero, 1 carattere speciale.</span>
                </div>

                <div class="form-group">
                    <label for="conferma_password">Conferma Password</label>
                    <input type="password" id="conferma_password" name="conferma_password" required>
                </div>

                <div class="form-group">
                    <label for="ruolo">Ruolo</label>
                    <select id="ruolo" name="ruolo" required>
                        <option value="user">Giocatore (User)</option>
                        <option value="admin">Gestore (Admin)</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Registrati e ricevi 100 Crediti</button> 
            </form>
        <?php endif; ?>

        <a href="login.php" class="link-back">Hai già un account? Accedi qui</a>
    </div>

</body>
</html>