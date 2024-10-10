
<div style="text-align: justify">

# Symfony: Justificación técnica

## Tabla de Contenidos
- [¿Por qué Symfony?](#por-qué-symfony)
- [Investigación](#investigación)
- [Doctrine](#doctrine)
- [Cosas 'Symfonycas'](#cosas-symfonycas)
- [Groups](#groups)
- [Optimización del código](#symfony-premia-tu-curiosidad-y-te-anima-a-optimizar-tu-código)
- [Enfoque](#enfoque)

## ¿Por qué Symfony?

La elección de Symfony puede parecer un tanto rocambolesca, sobre todo si se tiene en cuenta que esto es una prueba para un acceso a una Hackathon. Pero tengo buenas razones para escoger este Framework y no a su descendiente directo: Laravel.

## Investigación

Aunque Symfony está muy vivo en el ámbito empresarial y hay muchos negocios generando dinero con él, lo cierto es que no tiene tanta difusión didáctica ni tiene tantos recursos en inglés o en español (hay que recordar que Symfony está bajo el paraguas de una empresa con sede en Francia), y los que hay son de versiones antiguas de un Framework que, al ser tan modular, se permite el lujo de evolucionar más rápidamente que otros que cuentan con un núcleo más 'apelmazado'. Por poner ejemplos: La inclusión de componentes reactivos en su core (Symfony Ux Live Componentes) hace ya años, o la estandarización de las anotaciones de PHP 8, al poco de que existieran, para un montón de cosas que iremos mostrando en esta presentación.

## Doctrine

Doctrine es para Symfony lo que el balón para Óliver Atom. Llama la atención que un framework que es, casi siempre, tan modular, tenga una relación tan estrecha con su ORM, pero las ventajas del fuerte ensamblado entre Doctrine y otros componentes de Symfony como su Serializador o su Validador son muy extensas y vamos a nombrarlas ahora.

## Cosas 'Symfonycas'

Laravel es un framework excelente y lleno de automatizaciones que hacen que la vida del programador sea más sencilla, pero allí donde Laravel prioriza la transformación de la información, Symfony busca que el desarrollador piense en cómo optimizar los resultados. Pongamos un ejemplo: En una API Rest con Laravel transformaremos y formatearemos la información dependiendo de los requerimientos del endpoint sobre el que estemos trabajando. Laravel facilita la transformación de datos con el uso de sus Resources y de sus Collections, las cuales son rápidas de programar, sencillas de implementar y fáciles de extender o de modificar a pequeña escala. Sin embargo, se trata solo de 'una fachada'. Podríamos decir que Laravel tiende a hacer 'queries muy gruesas' a la vez que te tienta con herramientas para estilizar esta información.

Al mismo tiempo, cuando nos encontramos con situaciones cotidianas de validación, como cuando tenemos que afrontar distintos contextos dependiendo de si estamos creando algo o editándolo, Laravel pone a nuestra disposición distintos FormRequest para moldear estas validaciones, así como Resources, Collections, StoreRequests, UpdateRequests para el modelaje de la información presentada... Symfony se carga todo eso de un plumazo con un enfoque más sencillo:

## Groups

```php
#[ORM\Column(length: 180)]
    // Validaciones: Determinan CUÁNDO y CÓMO se valida el campo
    #[Assert\Email(
        message: 'Please enter a valid email',
        groups: ['create', 'edit']  // Se validará tanto en creación como en edición
    )]
    #[Assert\NotBlank(
        message: 'Please enter an email',
        groups: ['create', 'edit']  // Se validará tanto en creación como en edición
    )]
    // Serialización: Determina DÓNDE se puede ver/editar el campo
    #[Groups(['read', 'write'])]  // Se puede leer (GET) y escribir (POST/PUT), pero NO aparece en show
    private ?string $email = null;
```

Así es como luciría una sola de las propiedades de la Entidad User en Doctrine. Puede parecer un poco aparatoso, pero se ventila muchas de las necesidades que genera Laravel gracias a la idea de los Groups. Desentrañemos el significado de esto:

Los Groups sirven para clasificar nuestras propiedades, y existen de dos tipos, los Groups para las validaciones, y los Groups para el serializador.

Los Groups para las validaciones nos dicen si una validación debe estar presente en un contexto o no. Por ejemplo, en este propiedad email que hemos puesto como ejemplo más arriba, vemos que el 'Email' debe ser validado tanto en su creación como en su edición, pero si no quisiéramos permitir que el usuario editase su email, podríamos quitar el email del Group 'write' del serializador y, por ende, eliminar el 'edit' del Group de validación. Esto solventa la necesidad de tener que crear StoreRequest y UpdateRequest, pues con los Groups podemos adscribir las validaciones a distintos ámbitos de acción.

Los Groups para los serializadores están vinculados con los contextos de muestra o creación de los recursos. El 'Email' está presente en el grupo 'read' porque es el que incluiremos en nuestro método Index, para que sea leído en una colección de recursos. También 'Email' aparece en el Group 'write' porque queremos habilitar su serialización y su creación con los datos que nos envía el cliente. Por último, también incluimos el 'Email' en el grupo 'show' porque queremos que el 'Email' se muestre en el detalle del endpoint. Si, por ejemplo, no quisiéramos que el 'Email' se mostrara en el detalle de un usuario, bastaría con retirarlo del grupo 'show' y ya está.

Esta solución nos ahorra las listas blancas y negras de Laravel (los guard=[] y los fillable=[]) y también la necesidad de crear tantos Resources y Collections como transformaciones quiera aplicar a la información. Aunque los Resources Laravel son fáciles de extender en proyectos pequeños y medianos, para proyectos con muchas transformaciones ya no son tan cómodos.

## Symfony premia tu curiosidad y te anima a optimizar tu código

Cuando creas una Entidad con Symfony, además del código de Doctrine, se crea también un Repository que contiene algunos ejemplos para que te animes a optimizar tus consultas. En lugar de llenarse de métodos predefinidos y consultas preconstruidas, Symfony te da una libreta en blanco y te invita a dibujar según el flujo de tu información. El archivo EntidadRepository viene acompañado de estos ejemplos:

```php
//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
```

El framework te está invitando a crear tus propias consultas dependiendo de tus necesidades, y es ahí en donde te das cuenta del enfoque tan distinto que tiene a Laravel.

Ambos Frameworks son excelsos, y ambos pueden 'acercarse' a la filosofía inicial del otro: puedes optimizar las queries de Laravel con su fantástico QueryBuilder, y también puedes pasar del patrón Repository de Symfony y dejar que las consultas genéricas de Doctrine asuman la responsabilidad... Pero Symfony es un Framework que, de entrada, te hace hacerte más preguntas.

## Enfoque

Aunque el enunciado de la prueba no lo pide explícitamente, hemos decidido decantarnos por el empleo de JSON Web Token porque es un escenario más cotidiano y realista para una API Rest actual.

Además, como se podrá ver en los Controllers, hemos optado por un sistema de seguridad sencillo y fiable: en lugar de complicarnos escribiendo Voters (las Policies de Symfony), hemos visto más adecuado aferrarnos a los Tokens; de esta forma podemos suprimir los ID de las URL y obtener la información del usuario a través de la autenticación de Symfony. Con este proceder, es imposible que un usuario atenta contra alguien que no sea sí mismo.

No entraremos en más pormenores sobre la API en este Readme porque hemos decidido documentarla con Swagger. Esta documentación puede consultarse [aquí](http://localhost:8000/api/doc) una vez que la aplicación esté corriendo localmente.

## Advertencias

Se advierte, para quien no esté familiarizado con Symfony, que sus Controllers son necesariamente más verbosos que los de Laravel. Aquí no hay magia a la luz de la luna (Facades). Pero, en la medida de lo posible, se ha intentado que cada Controller solo sea responsable de una acción; simplemente la lógica no puede ocultarse tras el burladero de un Facade como ocurre con Laravel. Además, para mejorar la información captada por la documentación de Nelmio, hay muchas anotaciones añadidas sobre estos Controllers.

Las rutas difíciles de testear o sensibles, se han excluido de la documentación (aquellas que se han ideado pensando en ser utilizadas solo por el Admin y aquellas que requieren la inserción o la descarga de archivos JSON). Para consultar todas las rutas del proyecto, incluyendo las 'secretas', es recomendable ejecutar este comando desde la consola, en la raíz del proyecto: `php bin/console debug:router`


## Puesta en marcha del proyecto

Se ha creado una réplica sanitizada del '.env' original llamada '.env-example'. En esta réplica están indicados los campos con las credenciales que necesitan ser sustituidas para que el proyecto funcione.

Tras clonar el proyecto y correr el consabido `composer install` para instalar todas las dependencias, es necesario también correr el comando `php bin/console lexik:jwt:generate-keypair` para generar claves de encriptación para la librería encargada del JSON Web Token.

Una vez efectuados los pasos previos, es hora de crear la base de datos. Para ello ejecutaremos el comando `php bin/console doctrine:database:create` y, justo después, ejecutaremos también el comando para correr las migraciones: `php bin/console doctrine:migrations:migrate`.

Por último, y como se ha considerado como buena práctica habilitar algunas rutas solo para usuarios con el rol de administrador, se ha creado una fixture para sembrar un usuario administrador en la base de datos. Para hacer esto hay que correr el siguiente comando:
 `php bin/console doctrine:fixtures:load --append`

¡Ah!, y lo más importante, el servidor de pruebas de Symfony se lanza con `symfony serve` 

</div>
