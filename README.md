Laravel est un framework php et super sympa à prendre en main !
--
Documentation officiellle : https://laravel.com/
***

Tuto de prise en main de la  version 8 :
--
**Ce projet utilise la version 8 de laravel**. \
Tutoriel de prise en main : https://laravel.sillo.org/laravel-8/
***

Qu'est-ce que ATYPIKHOUSE
--
AtypikHouse est une société qui met en relation les propiétaires d'habitats qui souhaitent mettre  en location
des biens et les potentiels locataires.

***
A propos  de ce projet
--
Ce projet contient l'api et l'interface d'administration du site atypik-house-com.

   **1. L'API** :
   - Les routes de l'api se trouvent dans le dossier routes/api.php 
   - Les controllers appelés par chaque route se trouvent dans **app/Http/Controllers/Api**
     
   **2. LA PARTIE ADMIN** :
   - Les routes de l'api se trouvent dans le dossier **routes/web.php**
   - Les controllers appellés par chaque routes se trouvent dans **app/Http/Controllers/Admin**
***

Commande Laravel à exécuter à la récupération du projet
--
1. **`composer install`** : installe toutes les dépendances du projet
2. `php artisan key:generate` : Génère une clé pour le projet actuel
3. `php artisan migrate` : pour créer les tables dans la bd (configuez la bd dans le fichier **.env** au préalable) 
4. `php artisan db:seed` pour générer la création du compte admin (les identifiants sont présents dans le fichier .env) et la création d'un type habitat par défaut. 
5. `php artisan php artisan jwt:secret` pour générer la clé secrète permettant d'encrypter le token généré par Jwt
***

Lancer le projet en local  :
--
Pour lancer le projet, rendez-vous à la racine du projet et taper cette comme : `php artisan serve`
