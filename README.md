# Passport-Laravel
authentification avec passwort et systeme de payement avec notchpay

Laravel Passport fournit une implémentation complète du serveur OAuth2 pour votre application Laravel en quelques minutes. Passport est construit sur le serveur League OAuth2 géré par Andy Millington et Simon Hamp.


Cette documentation suppose que vous connaissez déjà OAuth2. Si vous ne connaissez rien à OAuth2, pensez à vous familiariser avec la terminologie générale et les fonctionnalités d'OAuth2 avant de continuer.

Passeport ou sanctuaire ?
Avant de commencer, vous souhaiterez peut-être déterminer si votre candidature serait mieux servie par Laravel Passport ou Laravel Sanctum . Si votre application doit absolument prendre en charge OAuth2, vous devez utiliser Laravel Passport.

Cependant, si vous essayez d'authentifier une application monopage, une application mobile ou d'émettre des jetons API, vous devez utiliser Laravel Sanctum . Laravel Sanctum ne prend pas en charge OAuth2 ; cependant, il offre une expérience de développement d’authentification API beaucoup plus simple.

Installation
Pour commencer, installez Passport via le gestionnaire de packages Composer :

composer require laravel/passport

Le fournisseur de services de Passport enregistre son propre répertoire de migration de base de données, vous devez donc migrer votre base de données après avoir installé le package. Les migrations Passport créeront les tables dont votre application a besoin pour stocker les clients OAuth2 et les jetons d'accès :

php artisan migrate

Ensuite, vous devez exécuter la passport:installcommande Artisan. Cette commande créera les clés de chiffrement nécessaires pour générer des jetons d'accès sécurisé. De plus, la commande créera des clients « accès personnel » et « octroi de mot de passe » qui seront utilisés pour générer des jetons d'accès :

php artisan passport:install


Si vous souhaitez utiliser les UUID comme valeur de clé primaire du Clientmodèle Passport au lieu d'entiers auto-incrémentés, veuillez installer Passport en utilisant l' uuidsoption .

Après avoir exécuté la passport:installcommande, ajoutez le Laravel\Passport\HasApiTokenstrait à votre App\Models\Usermodèle. Ce trait fournira quelques méthodes d'assistance à votre modèle qui vous permettront d'inspecter le jeton et les étendues de l'utilisateur authentifié. Si votre modèle utilise déjà le Laravel\Sanctum\HasApiTokenstrait, vous pouvez supprimer ce trait :

<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
 
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}

Enfin, dans le fichier de configuration de votre application config/auth.php, vous devez définir un apigarde d'authentification et définir l' driveroption sur passport. Cela demandera à votre application d'utiliser Passport TokenGuardlors de l'authentification des requêtes API entrantes :

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
 
    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
    ],
],

UUID client
Vous pouvez également exécuter la passport:installcommande avec l' --uuidsoption présente. Cette option indiquera à Passport que vous souhaitez utiliser des UUID au lieu d'entiers auto-incrémentés comme Clientvaleurs de clé primaire du modèle Passport. Après avoir exécuté la passport:installcommande avec l' --uuidsoption, vous recevrez des instructions supplémentaires concernant la désactivation des migrations par défaut de Passport :

php artisan passport:install --uuids

Déploiement de Passeport
Lors du premier déploiement de Passport sur les serveurs de votre application, vous devrez probablement exécuter la passport:keyscommande. Cette commande génère les clés de chiffrement dont Passport a besoin pour générer des jetons d'accès. Les clés générées ne sont généralement pas conservées dans le contrôle de source :

php artisan passport:keys

Si nécessaire, vous pouvez définir le chemin à partir duquel les clés de Passport doivent être chargées. Vous pouvez utiliser la Passport::loadKeysFromméthode pour y parvenir. Généralement, cette méthode doit être appelée depuis la bootméthode de la classe de votre applicationApp\Providers\AuthServiceProvider :

/**
 * Register any authentication / authorization services.
 */
public function boot(): void
{
    Passport::loadKeysFrom(__DIR__.'/../secrets/oauth');
}

Chargement des clés depuis l'environnement
Alternativement, vous pouvez publier le fichier de configuration de Passport à l'aide de la vendor:publishcommande Artisan :

php artisan vendor:publish --tag=passport-config

Une fois le fichier de configuration publié, vous pouvez charger les clés de chiffrement de votre application en les définissant comme variables d'environnement :

PASSPORT_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----
<private key here>
-----END RSA PRIVATE KEY-----"
 
PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
<public key here>
-----END PUBLIC KEY-----"

Personnalisation des migrations
Si vous n'utilisez pas les migrations par défaut de Passport, vous devez appeler la Passport::ignoreMigrationsméthode dans la registerméthode de votre App\Providers\AppServiceProviderclasse. Vous pouvez exporter les migrations par défaut à l'aide de la vendor:publishcommande Artisan :

php artisan vendor:publish --tag=passport-migrations

Mise à niveau du passeport
Lors de la mise à niveau vers une nouvelle version majeure de Passport, il est important de lire attentivement le guide de mise à niveau .

Configuration
Hachage du secret client
Si vous souhaitez que les secrets de votre client soient hachés lorsqu'ils sont stockés dans votre base de données, vous devez appeler la Passport::hashClientSecretsméthode dans la bootméthode de votre App\Providers\AuthServiceProviderclasse :

use Laravel\Passport\Passport;
 
Passport::hashClientSecrets();

Une fois activés, tous vos secrets clients ne seront visibles par l'utilisateur qu'immédiatement après leur création. Étant donné que la valeur du secret client en texte brut n'est jamais stockée dans la base de données, il n'est pas possible de récupérer la valeur du secret en cas de perte.

Durées de vie des jetons
Par défaut, Passport émet des jetons d'accès de longue durée qui expirent au bout d'un an. Si vous souhaitez configurer une durée de vie de jeton plus longue/plus courte, vous pouvez utiliser les méthodes tokensExpireIn, refreshTokensExpireInet personalAccessTokensExpireIn. Ces méthodes doivent être appelées depuis la bootméthode de la classe de votre applicationApp\Providers\AuthServiceProvider :

/**
 * Register any authentication / authorization services.
 */
public function boot(): void
{
    Passport::tokensExpireIn(now()->addDays(15));
    Passport::refreshTokensExpireIn(now()->addDays(30));
    Passport::personalAccessTokensExpireIn(now()->addMonths(6));
}


Les expires_atcolonnes des tables de la base de données Passport sont en lecture seule et à des fins d'affichage uniquement. Lors de l'émission de jetons, Passport stocke les informations d'expiration dans les jetons signés et cryptés. Si vous devez invalider un jeton, vous devez le révoquer .

Remplacement des modèles par défaut
Vous êtes libre d'étendre les modèles utilisés en interne par Passport en définissant votre propre modèle et en étendant le modèle Passport correspondant :

use Laravel\Passport\Client as PassportClient;
 
class Client extends PassportClient
{
    // ...
}

Après avoir défini votre modèle, vous pouvez demander à Passport d'utiliser votre modèle personnalisé via la Laravel\Passport\Passportclasse. En règle générale, vous devez informer Passport de vos modèles personnalisés dans la bootméthode de la classe de votre applicationApp\Providers\AuthServiceProvider :

use App\Models\Passport\AuthCode;
use App\Models\Passport\Client;
use App\Models\Passport\PersonalAccessClient;
use App\Models\Passport\RefreshToken;
use App\Models\Passport\Token;
 
/**
 * Register any authentication / authorization services.
 */
public function boot(): void
{
    Passport::useTokenModel(Token::class);
    Passport::useRefreshTokenModel(RefreshToken::class);
    Passport::useAuthCodeModel(AuthCode::class);
    Passport::useClientModel(Client::class);
    Passport::usePersonalAccessClientModel(PersonalAccessClient::class);
}

Remplacement des itinéraires
Parfois, vous souhaiterez peut-être personnaliser les itinéraires définis par Passport. Pour y parvenir, vous devez d'abord ignorer les itinéraires enregistrés par Passport en ajoutant Passport::ignoreRoutesdans la registerméthode de votre application AppServiceProvider:

use Laravel\Passport\Passport;
 
/**
 * Register any application services.
 */
public function register(): void
{
    Passport::ignoreRoutes();
}

Ensuite, vous pouvez copier les routes définies par Passport dans son fichier de routes vers le fichier de votre application routes/web.phpet les modifier à votre guise :

Route::group([
    'as' => 'passport.',
    'prefix' => config('passport.path', 'oauth'),
    'namespace' => '\Laravel\Passport\Http\Controllers',
], function () {
    // Passport routes...
});

Émission de jetons d'accès
L'utilisation d'OAuth2 via des codes d'autorisation est la façon dont la plupart des développeurs connaissent OAuth2. Lors de l'utilisation de codes d'autorisation, une application client redirigera un utilisateur vers votre serveur où il approuvera ou refusera la demande d'émission d'un jeton d'accès au client.

Gestion des clients
Premièrement, les développeurs créant des applications qui doivent interagir avec l'API de votre application devront enregistrer leur application auprès de la vôtre en créant un « client ». Généralement, cela consiste à fournir le nom de leur application et une URL vers laquelle votre application peut rediriger une fois que les utilisateurs ont approuvé leur demande d'autorisation.

La passport:clientcommande
Le moyen le plus simple de créer un client consiste à utiliser la passport:clientcommande Artisan. Cette commande peut être utilisée pour créer vos propres clients afin de tester votre fonctionnalité OAuth2. Lorsque vous exécutez la clientcommande, Passport vous demandera plus d'informations sur votre client et vous fournira un identifiant client et un secret :

php artisan passport:client

URL de redirection

Si vous souhaitez autoriser plusieurs URL de redirection pour votre client, vous pouvez les spécifier à l'aide d'une liste délimitée par des virgules lorsque la passport:clientcommande vous demande l'URL. Toutes les URL contenant des virgules doivent être codées en URL :

http://example.com/callback,http://examplefoo.com/callback

API JSON
Étant donné que les utilisateurs de votre application ne pourront pas utiliser la clientcommande, Passport fournit une API JSON que vous pouvez utiliser pour créer des clients. Cela vous évite d'avoir à coder manuellement les contrôleurs pour créer, mettre à jour et supprimer des clients.

Cependant, vous devrez associer l'API JSON de Passport à votre propre interface pour fournir un tableau de bord permettant à vos utilisateurs de gérer leurs clients. Ci-dessous, nous passerons en revue tous les points de terminaison de l'API pour la gestion des clients. Pour plus de commodité, nous utiliserons Axios pour démontrer l'envoi de requêtes HTTP aux points de terminaison.

L'API JSON est protégée par le middleware webet auth; par conséquent, il ne peut être appelé qu'à partir de votre propre application. Il ne peut pas être appelé depuis une source externe.

GET /oauth/clients
Cette route renvoie tous les clients de l'utilisateur authentifié. Ceci est principalement utile pour lister tous les clients de l'utilisateur afin qu'il puisse les modifier ou les supprimer :

axios.get('/oauth/clients')
    .then(response => {
        console.log(response.data);
    });

POST /oauth/clients
Cette route est utilisée pour créer de nouveaux clients. Cela nécessite deux données : celles du client nameet une redirectURL. L' redirectURL est l'endroit où l'utilisateur sera redirigé après avoir approuvé ou refusé une demande d'autorisation.

Lorsqu'un client est créé, un identifiant client et un secret client lui seront attribués. Ces valeurs seront utilisées lors de la demande de jetons d'accès à votre application. La route de création de client renverra la nouvelle instance client :

const data = {
    name: 'Client Name',
    redirect: 'http://example.com/callback'
};
 
axios.post('/oauth/clients', data)
    .then(response => {
        console.log(response.data);
    })
    .catch (response => {
        // List errors on response...
    });

PUT /oauth/clients/{client-id}
Cette route est utilisée pour mettre à jour les clients. Cela nécessite deux données : celles du client nameet une redirectURL. L' redirectURL est l'endroit où l'utilisateur sera redirigé après avoir approuvé ou refusé une demande d'autorisation. La route renverra l'instance client mise à jour :

const data = {
    name: 'New Client Name',
    redirect: 'http://example.com/callback'
};
 
axios.put('/oauth/clients/' + clientId, data)
    .then(response => {
        console.log(response.data);
    })
    .catch (response => {
        // List errors on response...
    });

DELETE /oauth/clients/{client-id}
Cette route est utilisée pour supprimer des clients :

axios.delete('/oauth/clients/' + clientId)
    .then(response => {
        // ...
    });

Demander des jetons
Redirection pour autorisation
Une fois qu'un client a été créé, les développeurs peuvent utiliser leur identifiant client et leur secret pour demander un code d'autorisation et un jeton d'accès à votre application. Tout d’abord, l’application consommatrice doit effectuer une demande de redirection vers l’itinéraire de votre application /oauth/authorizecomme suit :

use Illuminate\Http\Request;
use Illuminate\Support\Str;
 
Route::get('/redirect', function (Request $request) {
    $request->session()->put('state', $state = Str::random(40));
 
    $query = http_build_query([
        'client_id' => 'client-id',
        'redirect_uri' => 'http://third-party-app.com/callback',
        'response_type' => 'code',
        'scope' => '',
        'state' => $state,
        // 'prompt' => '', // "none", "consent", or "login"
    ]);
 
    return redirect('http://passport-app.test/oauth/authorize?'.$query);
});

Le promptparamètre peut être utilisé pour spécifier le comportement d'authentification de l'application Passport.

Si la promptvaleur est none, Passport générera toujours une erreur d'authentification si l'utilisateur n'est pas déjà authentifié auprès de l'application Passport. Si la valeur est consent, Passport affichera toujours l'écran d'approbation de l'autorisation, même si toutes les étendues ont été précédemment accordées à l'application consommatrice. Lorsque la valeur est login, l'application Passport invitera toujours l'utilisateur à se reconnecter à l'application, même s'il dispose déjà d'une session existante.

Si aucune promptvaleur n'est fournie, l'utilisateur sera invité à donner une autorisation uniquement s'il n'a pas préalablement autorisé l'accès à l'application consommatrice pour les étendues demandées.


N'oubliez pas que l' /oauth/authorizeitinéraire est déjà défini par Passport. Vous n'avez pas besoin de définir manuellement cet itinéraire.

Approuver la demande
Lors de la réception de demandes d'autorisation, Passport répondra automatiquement en fonction de la valeur du promptparamètre (le cas échéant) et pourra afficher un modèle à l'utilisateur lui permettant d'approuver ou de refuser la demande d'autorisation. S'ils approuvent la demande, ils seront redirigés vers celui redirect_urispécifié par l'application consommatrice. Il redirect_uridoit correspondre à l' redirectURL spécifiée lors de la création du client.

Si vous souhaitez personnaliser l'écran d'approbation des autorisations, vous pouvez publier les vues de Passport à l'aide de la vendor:publishcommande Artisan. Les vues publiées seront placées dans le resources/views/vendor/passportrépertoire :

php artisan vendor:publish --tag=passport-views

Parfois, vous souhaiterez peut-être ignorer l'invite d'autorisation, par exemple lors de l'autorisation d'un client propriétaire. Vous pouvez y parvenir en étendant le Clientmodèle et en définissant une skipsAuthorizationméthode. En cas skipsAuthorizationde retour, truele client sera approuvé et l'utilisateur sera redirect_uriimmédiatement redirigé vers le, à moins que l'application consommatrice n'ait explicitement défini le promptparamètre lors de la redirection pour autorisation :

<?php
 
namespace App\Models\Passport;
 
use Laravel\Passport\Client as BaseClient;
 
class Client extends BaseClient
{
    /**
     * Determine if the client should skip the authorization prompt.
     */
    public function skipsAuthorization(): bool
    {
        return $this->firstParty();
    }
}

Conversion des codes d'autorisation en jetons d'accès
Si l'utilisateur approuve la demande d'autorisation, il sera redirigé vers l'application consommatrice. Le consommateur doit d'abord vérifier le stateparamètre par rapport à la valeur stockée avant la redirection. Si le paramètre d'état correspond, le consommateur doit émettre une POSTdemande à votre application pour demander un jeton d'accès. La demande doit inclure le code d'autorisation émis par votre application lorsque l'utilisateur a approuvé la demande d'autorisation :

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
 
Route::get('/callback', function (Request $request) {
    $state = $request->session()->pull('state');
 
    throw_unless(
        strlen($state) > 0 && $state === $request->state,
        InvalidArgumentException::class,
        'Invalid state value.'
    );
 
    $response = Http::asForm()->post('http://passport-app.test/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect_uri' => 'http://third-party-app.com/callback',
        'code' => $request->code,
    ]);
 
    return $response->json();
});

Cette /oauth/tokenroute renverra une réponse JSON contenant les attributs access_token, refresh_tokenet expires_in. L' expires_inattribut contient le nombre de secondes jusqu'à l'expiration du jeton d'accès.


Tout comme l' /oauth/authorizeitinéraire, l' /oauth/tokenitinéraire est défini pour vous par Passeport. Il n'est pas nécessaire de définir manuellement cet itinéraire.

API JSON
Passport comprend également une API JSON pour gérer les jetons d'accès autorisés. Vous pouvez l'associer à votre propre interface pour offrir à vos utilisateurs un tableau de bord permettant de gérer les jetons d'accès. Pour plus de commodité, nous utiliserons Axios pour démontrer l'envoi de requêtes HTTP aux points de terminaison. L'API JSON est protégée par le middleware webet auth; par conséquent, il ne peut être appelé qu'à partir de votre propre application.

GET /oauth/tokens
Cette route renvoie tous les jetons d'accès autorisés créés par l'utilisateur authentifié. Ceci est principalement utile pour lister tous les tokens de l'utilisateur afin qu'il puisse les révoquer :

axios.get('/oauth/tokens')
    .then(response => {
        console.log(response.data);
    });

DELETE /oauth/tokens/{token-id}
Cette route peut être utilisée pour révoquer les jetons d'accès autorisés et leurs jetons d'actualisation associés :

axios.delete('/oauth/tokens/' + tokenId);

Jetons rafraîchissants
Si votre application émet des jetons d'accès de courte durée, les utilisateurs devront actualiser leurs jetons d'accès via le jeton d'actualisation qui leur a été fourni lors de l'émission du jeton d'accès :

use Illuminate\Support\Facades\Http;
 
$response = Http::asForm()->post('http://passport-app.test/oauth/token', [
    'grant_type' => 'refresh_token',
    'refresh_token' => 'the-refresh-token',
    'client_id' => 'client-id',
    'client_secret' => 'client-secret',
    'scope' => '',
]);
 
return $response->json();

Cette /oauth/tokenroute renverra une réponse JSON contenant les attributs access_token, refresh_tokenet expires_in. L' expires_inattribut contient le nombre de secondes jusqu'à l'expiration du jeton d'accès.

Révocation de jetons
Vous pouvez révoquer un jeton en utilisant la revokeAccessTokenméthode du Laravel\Passport\TokenRepository. Vous pouvez révoquer les jetons d'actualisation d'un jeton en utilisant la revokeRefreshTokensByAccessTokenIdméthode du Laravel\Passport\RefreshTokenRepository. Ces classes peuvent être résolues à l'aide du conteneur de services de Laravel :

use Laravel\Passport\TokenRepository;
use Laravel\Passport\RefreshTokenRepository;
 
$tokenRepository = app(TokenRepository::class);
$refreshTokenRepository = app(RefreshTokenRepository::class);
 
// Revoke an access token...
$tokenRepository->revokeAccessToken($tokenId);
 
// Revoke all of the token's refresh tokens...
$refreshTokenRepository->revokeRefreshTokensByAccessTokenId($tokenId);

Purge des jetons
Lorsque les jetons ont été révoqués ou ont expiré, vous souhaiterez peut-être les purger de la base de données. La commande Artisan incluse dans Passport passport:purgepeut le faire pour vous :

# Purge revoked and expired tokens and auth codes...
php artisan passport:purge
 
# Only purge tokens expired for more than 6 hours...
php artisan passport:purge --hours=6
 
# Only purge revoked tokens and auth codes...
php artisan passport:purge --revoked
 
# Only purge expired tokens and auth codes...
php artisan passport:purge --expired

Vous pouvez également configurer une tâche planifiée dans la classe de votre application App\Console\Kernelpour élaguer automatiquement vos jetons selon un calendrier :

/**
 * Define the application's command schedule.
 */
protected function schedule(Schedule $schedule): void
{
    $schedule->command('passport:purge')->hourly();
}
