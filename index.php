<?php
session_start(); //
require_once 'connessione.php';

try {
    $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Recupero lotterie aperte
    $sql = "SELECT id_lotteria, nome, prezzo_biglietto FROM lotteria WHERE aperta = 1 ORDER BY data_fine ASC";
    $stmt = $pdo->query($sql);
    $lotterie = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}

$is_logged_in = isset($_SESSION['id_utente']); //
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lotteria - Home</title>
    <style>
        /* ================= PALETTE COLORI GLOBALE ================= */
        :root {
            --primary: #1e3a8a;      /* Blu scuro elegante */
            --primary-hover: #1e40af;
            --accent: #f59e0b;       /* Oro/Ambra per i crediti */
            --bg-color: #f8fafc;     /* Sfondo pagina */
            --card-bg: #ffffff;      /* Sfondo elementi */
            --text-main: #334155;    /* Testo principale scuro ma non nero */
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

        /* GRIGLIA LOTTERIE */
        .lotterie-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            justify-items: center;
        }
        
        .lotteria-card { 
            background-color: var(--card-bg);
            border-radius: 10px; 
            padding: 25px 20px; 
            width: 100%;
            max-width: 280px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-decoration: none; 
            color: var(--text-main); 
            display: block;
            border-top: 5px solid var(--primary);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-sizing: border-box;
        }
        .lotteria-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 10px 15px rgba(0,0,0,0.1); 
            border-top-color: var(--accent);
        }
        
        .lotteria-nome { font-size: 1.2rem; font-weight: bold; margin-bottom: 15px; }
        .lotteria-prezzo { 
            display: inline-block;
            background-color: #fef3c7; 
            color: #d97706; 
            padding: 5px 12px; 
            border-radius: 20px; 
            font-size: 0.9rem; 
            font-weight: bold;
        }
    </style>
</head>
<body>

    <header class="header">
        <h1>LOTTERIE ONLINE</h1>
        <?php if ($is_logged_in): ?>
            <a href="profilo.php" class="btn">IL MIO PROFILO</a>
        <?php else: ?>
            <a href="login.php" class="btn">ACCEDI / REGISTRATI</a>
        <?php endif; ?>
    </header>

    <div class="container">
        <h2 class="section-title">Lotterie Disponibili</h2>

        <div class="lotterie-grid">
            <?php if (count($lotterie) > 0): ?>
                <?php foreach ($lotterie as $l): ?>
                    <?php 
                        // Se non è loggato, il click lo porta al login con un avviso
                        $link = $is_logged_in ? "dettaglio_lotteria.php?id=" . $l['id_lotteria'] : "login.php?msg=Devi+accedere+per+acquistare";
                    ?>
                    <a href="<?php echo $link; ?>" class="lotteria-card">
                        <div class="lotteria-nome"><?php echo htmlspecialchars(strtoupper($l['nome'])); ?></div>
                        <div class="lotteria-prezzo"><?php echo htmlspecialchars($l['prezzo_biglietto']); ?> Crediti</div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; color: var(--text-light);">Nessuna lotteria attiva in questo momento.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>