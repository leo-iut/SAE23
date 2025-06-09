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
            <p><a href="./images/screen.jpg"><img src="./images/screen.jpg" alt="image du gantt project" width="100%"></p></a>
        </section>

        <section>
            <h2>Outils Collaboratifs Utilisés</h2>
            <p>On a utilisé comme outils collaboratifs GitHub pour le partage des codes, dont le lien est le suivant : https://github.com/leo-iut/SAE23. De plus, Google Drive pour le partage d'informations et le rendu des livrables.</p>
        </section>

        <section>
            <h2>Synthèse Personnelle et Problèmes Rencontrés</h2>
            <h3>Membre 1 : Natan VIGER</h3>
            <p><strong>Travail effectué :</strong> J’ai effectué toute la partie Github avec toutes les commandes envoie de fichiers et créé le GITHUB, j’ai aidé pour la mise en forme du diagramme GANTT. fait une importante partie des codes du site web et de récupération des données MQTT. </p>
            <p><strong>Problèmes rencontrés :</strong> J’ai eu du mal à réaliser les codes et j’ai dû faire beaucoup de recherches pour améliorer mes connaissances notamment en PHP et en BASH le code de la SAÉ 15 m’a beaucoup aidé, ce qui m’a permis de réaliser ma partie du projet. </p>
            <p><strong>Solutions proposées :</strong> J’ai proposé les solutions de connexion xampp pour la sécurité avec des mots de passe, la connexion Filezilla, crontab.</p>

            <h3>Membre 2 : Leo TEYSSEDRE</h3>
            <p><strong>Travail effectué :</strong>J’ai effectué le diagramme de GANTT prévisionnel avec l’aide de Rémi et Natan. J’ai aussi aidé dans la récupération de données via MQTT. J’ai fait le flow Node-Red ainsi que le Grafana à l’aide de Rémi et Louis. J’ai aussi tout publié sur le serveur dédié xampp et fait une partie des codes.</p>
            <p><strong>Problèmes rencontrés :</strong>J’ai eu du mal à réaliser les scripts des fonctions, à afficher les capteurs et à récupérer les données pour les diffuser. </p>
            <p><strong>Solutions proposées :</strong> J’ai regardé de nombreuses vidéos pour m’aider à comprendre comment faire cela, ce qui m’a donc permis de faire tout ce qui était demandé sans trop de problèmes par la suite.</p>

            <h3>Membre 3 : Remi GAUTIER</h3>
            <p><strong>Travail effectué :</strong> J’ai effectué le diagramme de GANTT prévisionnel avec l’aide de Léo. J’ai réalisé une partie du flow Node-Red et Grafana. </p>
            <p><strong>Problèmes rencontrés :</strong>J’ai eu du mal à savoir comment construire les schémas Nod-Red de façon à récupérer les données et les afficher sur un schéma. </p>
            <p><strong>Solutions proposées :</strong>Pour surmonter ces obstacles, j’ai effectué des recherches approfondies et repris le TP réalisé précédemment dans le cadre de la SAÉ 23, ce qui m’a permis de mieux comprendre les concepts et d’optimiser les schémas. J’ai également consulté la documentation officielle de Node-RED et Grafana pour affiner la configuration et assurer une bonne récupération des données.</p>

            <h3>Membre 4 : Louis PORTET</h3>
            <p><strong>Travail effectué :</strong>J’ai effectué le schéma de conception de la base de données sur PhpMyAdmin. J’ai écrit tous les commentaires en anglais des codes et aidé dans la réalisation de Node-Red et Grafana.</p>
            <p><strong>Problèmes rencontrés :</strong>J’ai eu comme difficulté le fait de comprendre comment réaliser le projet, notamment comment récupérer les données via MQTT et les envoyer sur la base de données. Je n’arrivais plus à me connecter à Node-Red après avoir redémarré la VM et je ne comprenais pas pourquoi.</p>
            <p><strong>Solutions proposées :</strong>J’ai en fait vu que je n’avais pas mis lors de la création du docker la partie “--restart=always”. Ainsi, le docker ne se relançait pas tout seul après un redémarrage. Ensuite j’ai aussi visionné quelques vidéos pour mieux comprendre la récupération des données MQTT.</p>
        </section>

        <section>
            <h2>Conclusion et Satisfaction du Cahier des Charges</h2>
            <p>Nous avons réussi à réaliser tous les livrables en temps et en heure. Nous avons réalisé un Node-Red complet et un site web fonctionnel avec toutes les pages qui étaient demandées. Nous trouvons que nous avons globalement fait un bon travail et sommes contents de ce que nous avons réalisé.</p>
        </section>
    </main>

    <footer>
        <p>Projet SAÉ23 - IUT de Blagnac</p>
    </footer>
</body>
</html>
