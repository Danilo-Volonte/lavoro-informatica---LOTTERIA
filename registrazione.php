<?php
session_start();

// =========================================================================
// INCLUDIAMO LE LIBRERIE PHPMAILER
// Assicurati che la cartella "PHPMailer" sia nella stessa cartella di questo file!
// =========================================================================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$errore = "";
$successo = "";

// Variabili per mantenere i valori inseriti (Sticky Form)
$email_inserita = "";
$nickname_inserito = "";
$data_nascita_inserita = "";
// Il ruolo non è più preso dal form, è fisso per i nuovi iscritti
$ruolo_inserito = "user"; 

// Funzione validazione password
function validaPassword($pwd) {
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    return preg_match($pattern, $pwd);
}

// Funzione validazione maggiore età
function verificaDataNascita($data) {
    if (empty($data)) return false;
    $dataNascita = DateTime::createFromFormat('Y-m-d', $data);
    if (!$dataNascita) return false;
    $dataMinima = new DateTime('1910-01-01');
    $oggi = new DateTime();
    if ($dataNascita < $dataMinima) return false;
    $eta = $oggi->diff($dataNascita)->y;
    if ($eta < 18) return false;
    return true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'connessione.php';

    try {
        $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Salviamo i valori POST nelle variabili così non si perdono in caso di errore
        $email_inserita = trim($_POST['email']);
        $nickname_inserito = trim($_POST['nickname']);
        $data_nascita_inserita = $_POST['data_nascita'];
        
        // FORZIAMO IL RUOLO: Qualsiasi registrazione pubblica crea un "user"
        $ruolo_inserito = 'user'; 
        
        $password = $_POST['password'];
        $conferma_password = $_POST['conferma_password'];

        if ($password !== $conferma_password) {
            $errore = "Le password non coincidono!";
        } elseif (!validaPassword($password)) {
            $errore = "La password deve contenere almeno 8 caratteri, una maiuscola, una minuscola, un numero e un carattere speciale.";
        } elseif (!verificaDataNascita($data_nascita_inserita)) {
            $errore = "Devi avere almeno 18 anni per registrarti.";
        } else {
            $pw_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // 1. Generiamo il codice a 6 cifre casuale
            $codice_verifica = sprintf("%06d", mt_rand(1, 999999));

            // 2. Inseriamo l'utente bloccato (attivo = 0)
            $sql = "INSERT INTO utente (email, pw_hash, nickname, data_nascita, ruolo, attivo, codice_verifica) 
                    VALUES (:email, :pw_hash, :nickname, :data_nascita, :ruolo, 0, :codice_verifica)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':email' => $email_inserita,
                ':pw_hash' => $pw_hash,
                ':nickname' => $nickname_inserito,
                ':data_nascita' => $data_nascita_inserita,
                ':ruolo' => $ruolo_inserito,
                ':codice_verifica' => $codice_verifica
            ]);

            // =========================================================================
            // 3. INVIO EMAIL CON PHPMAILER E GMAIL (Con FIX per XAMPP)
            // =========================================================================
            $mail = new PHPMailer(true);

            try {
                // Impostazioni del Server SMTP
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';                     
                $mail->SMTPAuth   = true;                                   
                
                // INSERISCI QUI LA TUA EMAIL SECONDARIA GMAIL E LA PASSWORD PER LE APP
                $mail->Username   = 'progettolotteria@gmail.com';                     
                $mail->Password   = 'gwrliaspoceztaop';                               
                
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            
                $mail->Port       = 587;                                    

                // =========================================================
                // FIX MAGICO PER RISOLVERE L'ERRORE SSL DI XAMPP IN LOCALE
                // =========================================================
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                // =========================================================

                // Mittente e Destinatario (usa l'email temporanea che hai inserito nel form)
                $mail->setFrom('progettolotteria@gmail.com', 'Lotterie Online');
                $mail->addAddress($email_inserita, $nickname_inserito);     

                // Contenuto dell'email
                $mail->isHTML(true);                                  
                $mail->Subject = 'Attiva il tuo account Lotteria!';
                
                $messaggio_email = "
                <html>
                <body style='font-family: Arial, sans-serif; color: #333;'>
                    <h2>Benvenuto " . htmlspecialchars($nickname_inserito) . "!</h2>
                    <p>Grazie per esserti registrato. Per ricevere i tuoi 100 crediti bonus, devi attivare l'account.</p>
                    <p>Il tuo codice di verifica: <strong style='font-size: 24px; color: #1e3a8a; letter-spacing: 2px;'>$codice_verifica</strong></p>
                    <p>Inseriscilo nella pagina di verifica per completare la registrazione.</p>
                </body>
                </html>
                ";
                
                $mail->Body = $messaggio_email;

                // Spedisci l'email!
                $mail->send();
                
                // Se va tutto a buon fine, reindirizza alla pagina di verifica
                header("Location: verifica.php?email=" . urlencode($email_inserita));
                exit;

            } catch (Exception $e) {
                // L'utente è stato creato nel DB, ma la mail è fallita
                $errore = "Registrazione avvenuta, ma errore nell'invio dell'email: {$mail->ErrorInfo}. Trova il codice nel database.";
            }
            // =========================================================================
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $errore = "Email o Nickname già in uso. Scegline altri.";
        } else {
            $errore = "Errore del database: " . $e->getMessage();
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
        :root { --primary: #1e3a8a; --primary-hover: #1e40af; --accent: #f59e0b; --bg-color: #f8fafc; --card-bg: #ffffff; --text-main: #334155; --text-light: #64748b; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; box-sizing: border-box; }
        .form-container { background-color: var(--card-bg); padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 450px; border-top: 5px solid var(--primary); }
        .form-container h2 { margin-top: 0; color: var(--primary); text-align: center; margin-bottom: 25px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; }
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input { width: 100%; padding: 10px; padding-right: 40px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-family: inherit; transition: border-color 0.2s; }
        .password-wrapper input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1); }
        .toggle-password { position: absolute; right: 10px; background: none; border: none; cursor: pointer; font-size: 1.2rem; }
        .form-group > input, .form-group select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-family: inherit; transition: border-color 0.2s; }
        .form-group > input:focus, .form-group select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1); }
        .btn-submit { background-color: var(--primary); color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; margin-top: 10px; transition: background-color 0.3s; }
        .btn-submit:hover { background-color: var(--primary-hover); }
        .msg-errore { background-color: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-size: 0.9rem;}
        .link-back { display: block; text-align: center; margin-top: 20px; color: var(--text-light); text-decoration: none; font-size: 0.9rem; }
        .link-back:hover { color: var(--primary); text-decoration: underline; }
        .password-hint { font-size: 0.75rem; color: var(--text-light); margin-top: 4px; display: block; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Crea un Account</h2>
        
        <?php if (!empty($errore)): ?>
            <div class="msg-errore"><?php echo $errore; ?></div>
        <?php endif; ?>

        <form method="POST" action="registrazione.php">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email_inserita); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="nickname">Nickname</label>
                <input type="text" id="nickname" name="nickname" maxlength="30" value="<?php echo htmlspecialchars($nickname_inserito); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="data_nascita">Data di Nascita</label>
                <input type="date" id="data_nascita" name="data_nascita" value="<?php echo htmlspecialchars($data_nascita_inserita); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle-password" onclick="toggleVisibility('password', this)" title="Mostra/Nascondi">👁️</button>
                </div>
                <span class="password-hint">Min. 8 caratteri, 1 maiuscola, 1 minuscola, 1 numero, 1 carattere speciale.</span>
            </div>

            <div class="form-group">
                <label for="conferma_password">Conferma Password</label>
                <div class="password-wrapper">
                    <input type="password" id="conferma_password" name="conferma_password" required>
                    <button type="button" class="toggle-password" onclick="toggleVisibility('conferma_password', this)" title="Mostra/Nascondi">👁️</button>
                </div>
            </div>
            <button type="submit" class="btn-submit">Registrati</button> 
        </form>
        
        <a href="login.php" class="link-back">Hai già un account? Accedi qui</a>
    </div>

    <script>
        function toggleVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === "password") { 
                input.type = "text"; 
                btn.textContent = "🙈"; 
            } else { 
                input.type = "password"; 
                btn.textContent = "👁️"; 
            }
        }
    </script>
</body>
</html>