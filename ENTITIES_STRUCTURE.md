# Structure des Entités - SoldeTrack API

## 1. User (Utilisateur)

```dart
class User {
  int? id;
  String email;
  String nom;
  String prenoms;
  String? photo;
  DateTime? dateNaissance;
  DateTime dateCreation;
  Devise? devise;
  List<Mouvement> mouvements;
  List<Projet> projets;
  List<Categorie> categories;
  List<Contact> contacts;
  List<Compte> comptes;
  List<Transfert> transferts;
}
```

## 2. Devise (Devise)

```dart
class Devise {
  int? id;
  String code;
  String nom;
  List<User> users;
}
```

## 3. Compte (Compte/Wallet)

```dart
class Compte {
  int? id;
  User user;
  String nom;
  String? description;
  Devise devise;
  String soldeInitial;
  String soldeActuel;
  DateTime dateCreation;
  DateTime dateModification;
  bool actif;
  String type;
  String? numero;
  String? institution;
  List<Mouvement> mouvements;
}
```

## 4. Transfert (Transfert)

```dart
class Transfert {
  int? id;
  User user;
  Compte compteSource;
  Compte compteDestination;
  String montant;
  Devise devise;
  DateTime date;
  String? note;
  DateTime dateCreation;
}
```

## 5. Projet (Projet)

```dart
class Projet {
  int? id;
  User user;
  String nom;
  String? description;
  String? budgetPrevu;
  DateTime dateCreation;
  List<Mouvement> mouvements;
}
```

## 6. Categorie (Catégorie)

```dart
class Categorie {
  int? id;
  User user;
  String nom;
  String type;
  DateTime dateCreation;
  List<Mouvement> mouvements;
}
```

## 7. Contact (Contact)

```dart
class Contact {
  int? id;
  User user;
  String nom;
  String? telephone;
  String? email;
  String source;
  DateTime dateCreation;
  List<Mouvement> mouvements;
}
```

## 8. Mouvement (Mouvement - Table Parent)

```dart
class Mouvement {
  int? id;
  User user;
  String type;
  String montantTotal;
  String montantEffectif;
  String statut;
  DateTime date;
  String? description;
  Categorie? categorie;
  Projet? projet;
  Contact? contact;
  Compte compte;
  List<Paiement> paiements;
}
```

## 9. Depense (Dépense - Table Fille)

```dart
class Depense extends Mouvement {
  String? lieu;
  String? methodePaiement;
  String? recu;
}
```

## 10. Entree (Entrée - Table Fille)

```dart
class Entree extends Mouvement {
  String? source;
  String? methode;
}
```

## 11. Dette (Dette - Table Fille)

```dart
class Dette extends Mouvement {
  DateTime? echeance;
  String? taux;
  String montantRestant;
}
```

## 12. Don (Don - Table Fille)

```dart
class Don extends Mouvement {
  String? occasion;
}
```

## 13. Paiement (Paiement)

```dart
class Paiement {
  int? id;
  Mouvement mouvement;
  String montant;
  DateTime date;
  String? commentaire;
  String statut;
}
```

## Types/Enums

### Types de Mouvement
```dart
enum TypeMouvement {
  depense,
  entree,
  dette,
  don
}
```

### Statuts de Mouvement
```dart
enum StatutMouvement {
  en_attente,
  effectue,
  annule
}
```

### Types de Catégorie
```dart
enum TypeCategorie {
  depense,
  entree,
  dette,
  don
}
```

### Sources de Contact
```dart
enum SourceContact {
  famille,
  ami,
  collegue,
  client,
  fournisseur,
  autre
}
```

### Types de Compte
```dart
enum TypeCompte {
  compte_principal,
  epargne,
  momo,
  carte,
  especes,
  banque,
  crypto,
  autre
}
```

### Statuts de Paiement
```dart
enum StatutPaiement {
  en_attente,
  effectue,
  annule
}
```

## Relations Principales

- **User** → **1:N** → **Compte, Mouvement, Projet, Categorie, Contact, Transfert**
- **Compte** → **1:N** → **Mouvement**
- **Mouvement** → **1:N** → **Paiement**
- **User** → **N:1** → **Devise**
- **Compte** → **N:1** → **Devise**
- **Transfert** → **N:1** → **Compte (Source & Destination)**
- **Mouvement** → **N:1** → **Categorie, Projet, Contact, Compte**
