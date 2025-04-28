# tsundoku_backend

## 🚀 1️⃣ Liste des fonctionnalités

### 📌 1. Authentification & Gestion des Utilisateurs

#### Fichiers concernés :
•	AuthController.php
•	RegisterController.php
•	UserController.php
•	ResetPasswordController.php
•	ProfileController.php
•	ProfilePhotoController.php

#### Fonctionnalités globales :
•	🔹 Inscription (RegisterController)
•	🔹 Connexion (AuthController)
•	🔹 Gestion du mot de passe (ResetPasswordController)
•	🔹 Mise à jour des informations utilisateur (UserController)
•	🔹 Gestion des photos de profil (ProfilePhotoController)

### 📌 2. Gestion des Livres & Contenus

#### Fichiers concernés :
•	BookController.php
•	PostController.php
•	CommentController.php
•	MessageController.php

#### Fonctionnalités globales :
•	📖 Gestion des livres (BookController)
•	📝 Création et gestion des posts (PostController)
•	💬 Gestion des commentaires (CommentController)
•	📩 Système de messagerie privée (MessageController)

### 📌 3. Gestion des Relations Sociales

### Fichiers concernés :
•	FollowerController.php
•	FriendshipController.php
•	GroupController.php
•	GroupProfileController.php

### Fonctionnalités globales :
•	👥 Suivi et abonnements (FollowerController)
•	🤝 Ajout et suppression d’amis (FriendshipController)
•	🏠 Gestion des groupes (GroupController)
•	📌 Attribution des rôles dans un groupe (GroupProfileController)

### 📌 4. Communication & Discussions

#### Fichiers concernés :
•	ConversationController.php
•	MessageController.php

#### Fonctionnalités globales :
•	💬 Système de messagerie (MessageController)
•	🗣 Système de conversations privées & de groupe (ConversationController)

## 🚀 2️⃣ Fonctionnalités Détaillées

### 📌 Authentification & Gestion des Utilisateurs

| 📌 Fonctionnalité                        | 🔹 Méthode  | 🌍 Endpoint                      |
|------------------------------------------|------------|----------------------------------|
| ✨ Inscription utilisateur               | `POST`     | `/api/register`                  |
| 🔑 Connexion utilisateur                 | `POST`     | `/api/login`                     |
| 👤 Récupération des infos utilisateur    | `GET`      | `/api/v1/user/{userId}`          |
| ✏️ Mise à jour du profil                 | `PATCH`    | `/api/v1/user/{userId}`          |
| 🔄 Réinitialisation du mot de passe      | `POST`     | `/api/v1/reset/password/request` |
| 🔐 Modifier son mot de passe             | `PATCH`    | `/api/v1/reset/password/update`  |
| 🖼 Ajouter une photo de profil           | `POST`     | `/api/v1/profile/photo/upload`   |

### 📌 2️⃣ Gestion des Livres & Contenus

| 📌 Fonctionnalité                           | 🔹 Méthode  | 🌍 Endpoint                                  |
|---------------------------------------------|------------|---------------------------------------------|
| 📚 Ajouter un livre                         | `POST`     | `/api/v1/book/add`                          |
| ✏️ Modifier un livre                        | `PATCH`    | `/api/v1/book/{bookId}`                     |
| ❌ Supprimer un livre                       | `DELETE`   | `/api/v1/book/{bookId}`                     |
| 📝 Ajouter un post                          | `POST`     | `/api/v1/posts/add`                          |
| ✏️ Modifier un post                         | `PATCH`    | `/api/v1/posts/{postId}`                     |
| ❌ Supprimer un post                        | `DELETE`   | `/api/v1/posts/{postId}`                     |
| 💬 Ajouter un commentaire                   | `POST`     | `/api/v1/comment/add`                       |
| 📜 Récupérer les commentaires d’un post     | `GET`      | `/api/v1/comment/{postId}/comments`         |
| 🔄 Récupérer un commentaire et ses enfants  | `GET`      | `/api/v1/comment/{commentId}/children`      |
### 📌 3️⃣ Relations Sociales

| 📌 Fonctionnalité              | 🔹 Méthode  | 🌍 Endpoint                                  |
|--------------------------------|------------|---------------------------------------------|
| ➕ Suivre un utilisateur       | `POST`     | `/api/v1/follower/{userId}/follow`         |
| ➖ Se désabonner               | `DELETE`   | `/api/v1/follower/{userId}/unfollow`       |
| 🤝 Ajouter un ami              | `POST`     | `/api/v1/friendship/{userId}/add`         |
| ❌ Retirer un ami              | `DELETE`   | `/api/v1/friendship/{userId}/remove`      |
| 🏠 Créer un groupe             | `POST`     | `/api/v1/group/create`                     |

### 📌 4️⃣ Communication & Discussions

| 📌 Fonctionnalité                            | 🔹 Méthode  | 🌍 Endpoint                                        |
|----------------------------------------------|------------|-------------------------------------------------|
| ✉️ Envoyer un message privé                  | `POST`     | `/api/v1/message/send`                         |
| 📩 Lire les messages d’une conversation      | `GET`      | `/api/v1/message/{conversationId}` |
| 🗣 Créer une conversation                    | `POST`     | `/api/v1/conversation/create`                 |
| ➕ Ajouter un utilisateur à une conversation | `POST`     | `/api/v1/conversation/{conversationId}/add-user` |
| ❌ Supprimer un utilisateur d’une conversation | `DELETE`   | `/api/v1/conversation/{conversationId}/remove-user` |