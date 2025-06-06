<?php
session_start(); // Start the session to enable dynamic navigation links based on user role.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Projet - SAÉ23 IoT</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Gestion de Projet SAÉ23</h1>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="consultation.php">Consultation</a></li>
                <?php if (isset($_SESSION['role'])): // Check if a user is logged in. ?>
                    <?php if ($_SESSION['role'] === 'Administrateur'): // If the user is an Administrator, show the 'Administration' link. ?>
                        <li><a href="admin.php">Administration</a></li>
                    <?php elseif ($_SESSION['role'] === 'Gestionnaire'): // If the user is a Manager, show the 'Gestion' link. ?>
                        <li><a href="gestion.php">Gestion</a></li>
                    <?php endif; ?>
                    <li><a href="deconnexion.php">Déconnexion</a></li> <?php else: // If no user is logged in, show the 'Connexion' link. ?>
                    <li><a href="connexion.php">Connexion</a></li>
                <?php endif; ?>
                <li><a href="gestion_projet.php" class="active">Gestion Projet</a></li> </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Diagramme de GANTT Final</h2>
            <p>Insérez ici l'image de votre diagramme de GANTT final. C'est un des livrables de la SAÉ23.</p>
        </section>

        <section>
            <h2>Outils Collaboratifs Utilisés</h2>
            <p>Décrivez et/ou insérez des captures d'écran des outils que vous avez utilisés pour la collaboration et la planification, tels que Trello, Git/GitHub, et Google Drive.</p>
        </section>

        <section>
            <h2>Synthèse Personnelle et Problèmes Rencontrés</h2>
            <p>Chaque membre de l'équipe doit fournir une synthèse personnelle sur son travail précis réalisé, les problèmes rencontrés et les solutions proposées.</p>
            <h3>Membre 1 : [Votre Nom]</h3>
            <p><strong>Travail effectué :</strong> Décrivez vos contributions spécifiques (ex: conception de la base de données, développement de la page d'administration, intégration MQTT).</p>
            <p><strong>Problèmes rencontrés :</strong> Détaillez les défis (ex: difficultés avec les requêtes SQL complexes, erreurs de connexion PHP, gestion des sessions, problèmes Docker).</p>
            <p><strong>Solutions proposées :</strong> Expliquez comment vous avez résolu ces problèmes.</p>

            <h3>Membre 2 : [Nom du Collaborateur]</h3>
            <p><strong>Travail effectué :</strong> ...</p>
            <p><strong>Problèmes rencontrés :</strong> ...</p>
            <p><strong>Solutions proposées :</strong> ...</p>
        </section>

        <section>
            <h2>Conclusion et Satisfaction du Cahier des Charges</h2>
            <p>Faites un bilan du projet. Dans quelle mesure la solution finale répond-elle aux exigences du cahier des charges ? Mettez en avant les points forts et les éventuelles améliorations possibles.</p>
        </section>
    </main>

    <footer>
        <p>Projet SAÉ23 - IUT de Blagnac</p>
    </footer>
</body>
</html>
