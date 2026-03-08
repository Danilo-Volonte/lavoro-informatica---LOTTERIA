<?php
// Includiamo PHPMailer per inviare le email di vincita
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

try {
    // 1. Cerchiamo le lotterie scadute e ancora aperte
    $sql_scadute = "SELECT id_lotteria, nome, n_biglietti_vincenti FROM lotteria WHERE data_fine <= NOW() AND aperta = 1";
    $stmt_scadute = $pdo->query($sql_scadute);
    $lotterie_da_estrarre = $stmt_scadute->fetchAll(PDO::FETCH_ASSOC);

    if (count($lotterie_da_estrarre) > 0) {
        foreach ($lotterie_da_estrarre as $lott) {
            $id_lott = $lott['id_lotteria'];
            $nome_lotteria = $lott['nome'];
            $n_vincitori = $lott['n_biglietti_vincenti'];

            // 2. CHIUDIAMO LA LOTTERIA
            $pdo->exec("UPDATE lotteria SET aperta = 0 WHERE id_lotteria = $id_lott");

            // =========================================================
            // FIX: ORDER BY posizione ASC (al singolare!)
            // =========================================================
            $stmt_premi = $pdo->prepare("SELECT vincita FROM premio WHERE id_lotteria = ? ORDER BY posizione ASC");
            $stmt_premi->execute([$id_lott]);
            $premi = $stmt_premi->fetchAll(PDO::FETCH_COLUMN);

            // 4. Peschiamo i biglietti vincenti a caso!
            $stmt_vincenti = $pdo->prepare("SELECT id_utente, id_biglietto FROM biglietto WHERE id_lotteria = :id_lott ORDER BY RAND() LIMIT :limite");
            $stmt_vincenti->bindValue(':id_lott', $id_lott, PDO::PARAM_INT);
            $stmt_vincenti->bindValue(':limite', $n_vincitori, PDO::PARAM_INT);
            $stmt_vincenti->execute();
            $biglietti_estratti = $stmt_vincenti->fetchAll(PDO::FETCH_ASSOC);

            // 5. Paghiamo gli utenti E INVIAMO LA MAIL!
            $stmt_paga = $pdo->prepare("UPDATE utente SET crediti = crediti + ? WHERE id_utente = ?");
            $stmt_dati_utente = $pdo->prepare("SELECT email, nickname FROM utente WHERE id_utente = ?");
            
            foreach ($biglietti_estratti as $index => $ticket) {
                if (isset($premi[$index])) {
                    $vincita = $premi[$index];
                    $id_vincitore = $ticket['id_utente'];
                    
                    // A. Versiamo i soldi all'utente nel DB
                    $stmt_paga->execute([$vincita, $id_vincitore]);

                    // B. Recuperiamo la sua email
                    $stmt_dati_utente->execute([$id_vincitore]);
                    $utente_vincitore = $stmt_dati_utente->fetch(PDO::FETCH_ASSOC);

                    if ($utente_vincitore) {
                        $email_destinatario = $utente_vincitore['email'];
                        $nickname_destinatario = $utente_vincitore['nickname'];

                        // C. SPEDIAMO L'EMAIL DI VITTORIA!
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';                     
                            $mail->SMTPAuth   = true;                                   
                            
                            // INSERISCI QUI I TUOI DATI GMAIL SECONDARI
                            $mail->Username   = 'progettolotteria@gmail.com';                     
                            $mail->Password   = 'gwrliaspoceztaop';                               
                            
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            
                            $mail->Port       = 587;                                    

                            // Fix per XAMPP
                            $mail->SMTPOptions = array(
                                'ssl' => array(
                                    'verify_peer' => false,
                                    'verify_peer_name' => false,
                                    'allow_self_signed' => true
                                )
                            );

                            $mail->setFrom('progettolotteria@gmail.com', 'Lotterie Online');
                            $mail->addAddress($email_destinatario, $nickname_destinatario);     

                            $mail->isHTML(true);                                  
                            $mail->Subject = "Hai vinto alla lotteria $nome_lotteria!";
                            
                            $mail->Body = "
                            <html>
                            <body style='font-family: Arial, sans-serif; color: #333; text-align: center;'>
                                <h2>Congratulazioni " . htmlspecialchars($nickname_destinatario) . "! 🎉</h2>
                                <p>Il tuo biglietto per la lotteria <b>" . htmlspecialchars(strtoupper($nome_lotteria)) . "</b> è stato estratto!</p>
                                <p>Hai vinto <strong style='color: #f59e0b; font-size: 24px;'>" . $vincita . " Crediti</strong></p>
                                <p>I crediti sono stati accreditati sul tuo conto. Accedi al tuo profilo per controllare il saldo.</p>
                            </body>
                            </html>
                            ";

                            $mail->send();
                        } catch (Exception $e) {
                            echo "<div style='background: #fee2e2; color: #991b1b; padding: 10px; margin-bottom: 10px; border: 2px solid red;'>
                                  <b>ERRORE MAIL:</b> Impossibile inviare a $email_destinatario. Errore: {$mail->ErrorInfo}
                                  </div>";
                        }
                    }
                }
            }
        }
    }
} catch (PDOException $e) {
    echo "<div style='background: #fee2e2; color: #991b1b; padding: 10px; margin-bottom: 10px; border: 2px solid red;'>
          <b>ERRORE DATABASE ESTRAZIONE:</b> " . $e->getMessage() . "
          </div>";
}
?>