<?php

namespace App\Controller;

use App\Entity\User;
use OpenApi\Attributes as OA;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[OA\Tag(name: 'User', description: 'User management endpoints')]
#[Route('/api/user', name: 'app_user_')]
class UserController extends AbstractController
{
    #[Route('/list', name: 'list', methods: ['GET'])]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'List of users',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: User::class))
                )
            ]
        )
    )]
    public function list(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        $data = $serializer->normalize($users, null, ['groups' => ['read']]);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/show', name: 'show', methods: ['GET'])]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Show user details',
        content: new OA\JsonContent(ref: new Model(type: User::class))
    )]
    public function show(SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();

        $data = $serializer->normalize($user, null, ['groups' => ['show']]);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/edit', name: 'edit', methods: ['PUT', 'PATCH'])]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'User updated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'User updated')
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Validation errors',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'))
            ]
        )
    )]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        $user = $this->getUser();

        $serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $user,
                'groups' => ['write']
            ]
        );

        $errors = $validator->validate($user, null, ['edit']);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsArray], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return new JsonResponse(
            $serializer->normalize($user, null, ['groups' => ['read']]),
            Response::HTTP_OK
        );
    }
    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    #[OA\Response(
        response: Response::HTTP_NO_CONTENT,
        description: 'User successfully deleted',
        content: null
    )]
    public function delete(EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
