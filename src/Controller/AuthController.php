<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth', description: 'Authentication endpoints')]
#[Route('/api', name: 'api_')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        operationId: 'registerUser',
        summary: 'Register a new user',
        description: 'Creates a new user account with the provided information'
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password', 'name', 'lastName', 'username', 'birthday'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'StrongPass123!'),
                new OA\Property(property: 'name', type: 'string', example: 'John'),
                new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
                new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                new OA\Property(property: 'birthday', type: 'string', format: 'date', example: '1990-01-01')
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'User successfully created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'User created')
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_CONFLICT,
        description: 'Email already exists',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Email already exists')
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Validation errors',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'errors',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['Email is not valid', 'Password must be at least 6 characters']
                )
            ]
        )
    )]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        if ($existingUser) {
            return new JsonResponse(['message' => 'Email already exists'], Response::HTTP_CONFLICT);
        }
        $errors = $validator->validate($user, null, ['create']);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsArray], Response::HTTP_BAD_REQUEST);
        }
        $user->setPassword($userPasswordHasher->hashPassword($user, $user->getPassword()));
        $entityManager->persist($user);
        $entityManager->flush();
        return new JsonResponse(['message' => 'User created'], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login',
        operationId: 'loginUser',
        summary: 'Login user',
        description: 'Authenticate user and return JWT token'
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'StrongPass123!')
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Login successful',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'token',
                    type: 'string',
                    example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'
                )
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'Invalid credentials',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Invalid credentials.')
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Missing credentials',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Email and password cannot be empty.')
            ]
        )
    )]
    public function login(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
        if (empty($email) || empty($password)) {
            return new JsonResponse(['error' => 'Email and password cannot be empty.'], Response::HTTP_BAD_REQUEST);
        }
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user || !$userPasswordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }
        $token = $jwtManager->create($user);
        return new JsonResponse(['token' => $token], Response::HTTP_OK);
    }
}
