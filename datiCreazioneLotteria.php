<?php
session_start();
require 'connessione.php';

// Controlli di sicurezza: solo admin loggati possono accedere
if (!isset($_SESSION['id_utente']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$is_logged_in = isset($_SESSION['id_utente']);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserimento Dati Lotteria - Admin</title>
    <style>
        /* ====== PALETTE E STILI GLOBALI ====== */
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
        }

        /* HEADER */
        .header {
            background-color: var(--primary);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header h1 { margin: 0; font-size: 1.8rem; letter-spacing: 1px; }

        /* BOTTONI GENERALI */
        .btn {
            background-color: var(--card-bg);
            color: var(--primary);
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .btn:hover { background-color: #e2e8f0; }

        /* CONTENITORE PRINCIPALE */
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; text-align: center; }

        .section-title {
            display: inline-block;
            font-size: 1.5rem;
            color: var(--primary);
            border-bottom: 3px solid var(--accent);
            padding-bottom: 5px;
            margin-bottom: 40px;
            font-weight: bold;
        }

        /* FORM */
        .form-container {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border-top: 5px solid var(--primary);
        }

        .form-container h2 { margin-top: 0; color: var(--primary); text-align: center; margin-bottom: 25px; }

        .form-group { margin-bottom: 18px; text-align: left; }
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

        /* LINK INFERIORI */
        .links { margin-top: 25px; text-align: center; font-size: 0.9rem; }
        .links a { color: var(--primary); text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .links a:hover { color: var(--accent); text-decoration: underline; }

        /* RESPONSIVE */
        @media (max-width: 600px) {
            .container { padding: 0 10px; }
            .form-container { padding: 20px; }
        }
    </style>
</head>
<body>

    <header class="header">
        <h1>LOTTERIE ONLINE - ADMIN</h1>
        <?php if ($is_logged_in): ?>
            <a href="profilo.php" class="btn">IL MIO PROFILO</a>
        <?php else: ?>
            <a href="login.php" class="btn">ACCEDI</a>
        <?php endif; ?>
    </header>

    <div class="container">
        <h2 class="section-title">Inserimento Dati per Creare una Lotteria</h2>

        <div class="form-container">
            <h2>Inserisci Dati</h2>

            <form action="creaLotteria.php" method="POST">
                <input type="hidden" name="idUtente" value="<?php echo htmlspecialchars($_POST['idUtente'] ?? ''); ?>">

                <div class="form-group">
                    <label for="nomeLotteria">Nome della Lotteria</label>
                    <input type="text" id="nomeLotteria" name="nomeLotteria" placeholder="Es. Spring Fortune" required>
                </div>

                <div class="form-group">
                    <label for="dataFine">Data Fine Lotteria</label>
                    <input type="date" id="dataFine" name="dataFine" required>
                </div>

                <div class="form-group">
                    <label for="prezzoBiglietto">Prezzo del Biglietto (Crediti, 1-5)</label>
                    <input type="number" id="prezzoBiglietto" name="prezzoBiglietto" placeholder="3" min="1" max="5" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="bigliettiTotali">Numero Totale dei Biglietti</label>
                    <input type="number" id="bigliettiTotali" name="bigliettiTotali" placeholder="100" min="1" step="1" required>
                </div>

                <div class="form-group">
                    <label for="bigliettiVincenti">Numero dei Biglietti Vincenti</label>
                    <input type="number" id="bigliettiVincenti" name="bigliettiVincenti" placeholder="10" min="1" step="1" required>
                </div>

                <button type="submit" class="btn-submit">Crea Lotteria</button>
            </form>

            <div class="links">
                <a href="profilo.php">&larr; Torna al Profilo</a>
            </div>
        </div>
    </div>

</body>
</html>