<?php
session_start(); // Start the session to manage user login state.
require_once 'db_connect.php'; // Include the database connection file.

$message_erreur = ''; // Initialize an empty string for error messages.

// Check if the form was submitted using the POST method.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = $_POST['identifiant']; // Get the username from the form.
    $mot_de_passe = $_POST['mot_de_passe']; // Get the password from the form.

    // Attempt to log in as an Administrator.
    // Prepare a SQL query to find an administrator by login.
    $stmt_admin = mysqli_prepare($conn, "SELECT id_administrateur, mdp FROM Administrateur WHERE login = ?");
    mysqli_stmt_bind_param($stmt_admin, "s", $identifiant); // Bind the username parameter.
    mysqli_stmt_execute($stmt_admin); // Execute the query.
    $result_admin = mysqli_stmt_get_result($stmt_admin); // Get the result set.

    // If an administrator is found.
    if (mysqli_num_rows($result_admin) == 1) {
        $admin = mysqli_fetch_assoc($result_admin); // Fetch the administrator's data.
        // For this project (SAE), a simple password comparison is used.
        // In a real production environment, you should use password_verify($mot_de_passe, $admin['mdp']) for security.
        if ($mot_de_passe === $admin['mdp']) {
            $_SESSION['user_id'] = $admin['id_administrateur']; // Store user ID in session.
            $_SESSION['role'] = 'Administrateur'; // Set user role.
            header("Location: admin.php"); // Redirect to the admin page.
            exit(); // Stop script execution.
        } else {
            $message_erreur = "Identifiant ou mot de passe incorrect."; // Set error message for wrong password.
        }
    } else {
        // If no administrator is found, attempt to log in as a Manager (Gestionnaire).
        $stmt_gest = mysqli_prepare($conn, "SELECT id_batiment, NomGest, MdpGest FROM Batiment WHERE NomGest = ?");
        mysqli_stmt_bind_param($stmt_gest, "s", $identifiant);
        mysqli_stmt_execute($stmt_gest);
        $result_gest = mysqli_stmt_get_result($stmt_gest);

        // If a manager is found.
        if (mysqli_num_rows($result_gest) == 1) {
            $gestionnaire = mysqli_fetch_assoc($result_gest); // Fetch manager's data.
            if ($mot_de_passe === $gestionnaire['MdpGest']) {
                $_SESSION['user_id'] = $gestionnaire['id_batiment']; // Store building ID in session.
                $_SESSION['username'] = $gestionnaire['NomGest']; // Store manager's username.
                $_SESSION['role'] = 'Gestionnaire'; // Set user role.
                header("Location: gestion.php"); // Redirect to the manager page.
                exit();
            } else {
                $message_erreur = "Identifiant ou mot de passe incorrect."; // Set error message for wrong password.
            }
        } else {
            $message_erreur = "Identifiant ou mot de passe incorrect."; // Set error message if no user found.
        }
    }
    mysqli_stmt_close($stmt_admin); // Close the admin statement.
    if (isset($stmt_gest)) { mysqli_stmt_close($stmt_gest); } // Close the manager statement if it was initialized.
}
mysqli_close($conn); // Close the database connection.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Plateforme IoT SAÉ23</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Connexion</h1>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="consultation.php">Consultation</a></li>
                <li><a href="connexion.php" class="active">Connexion</a></li>
                <li><a href="gestion_projet.php">Gestion Projet</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Veuillez vous connecter</h2>
            <?php if ($message_erreur): // Display error message if it exists. ?>
                <p class="message error"><?php echo htmlspecialchars($message_erreur); ?></p>
            <?php endif; ?>
            <form action="connexion.php" method="POST">
                <label for="identifiant">Identifiant :</label>
                <input type="text" id="identifiant" name="identifiant" required><br>

                <label for="mot_de_passe">Mot de passe :</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required><br>

                <input type="submit" value="Se connecter">
            </form>
        </section>
    </main>

    <footer>
        <p>Projet SAÉ23 - IUT de Blagnac</p>
    </footer>
</body>
</html>
