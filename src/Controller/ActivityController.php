<?php

namespace App\Controller;

use App\Entity\Activity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Activity', description: 'Activity management endpoints')]
#[Route('/api/activity', name: 'app_activity_')]
class ActivityController extends AbstractController
{
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Activity created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Activity created')
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
    public function create(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        $activity = $serializer->deserialize($request->getContent(), Activity::class, 'json');

        $errors = $validator->validate($activity);

        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsArray], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($activity);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Activity created'], Response::HTTP_CREATED);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'List of activities',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Activity')
                )
            ]
        )
    )]
    public function list(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $activities = $entityManager->getRepository(Activity::class)->findAll();

        $data = $serializer->normalize($activities, null, ['groups' => ['read']]);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/join/{activity}', name: 'join', methods: ['POST'])]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'User joined the activity',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'User joined')
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'User already joined',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'User already joined')
            ]
        )
    )]
    public function join(EntityManagerInterface $entityManager, Activity $activity): JsonResponse
    {
        $participantToAdd = $this->getUser();

        if ($activity->getParticipants()->contains($participantToAdd)) {
            return new JsonResponse(['message' => 'User already joined'], Response::HTTP_OK);
        }

        $activity->addParticipant($participantToAdd);

        $entityManager->flush();

        return new JsonResponse(['message' => 'User joined'], Response::HTTP_OK);
    }

    #[Route('/export', name: 'export', methods: ['GET'])]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Export activities',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Activity')
                )
            ]
        )
    )]
    public function export(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $activities = $entityManager->getRepository(Activity::class)->findAll();

        $data = $serializer->normalize($activities, null, ['groups' => ['read']]);

        return new JsonResponse($data, Response::HTTP_OK, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="activities.json"',
        ]);
    }

    #[Route('/import', name: 'import', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Activities imported successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Activities imported successfully')
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
    public function import(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return new JsonResponse(['error' => 'Invalid file'], Response::HTTP_BAD_REQUEST);
        }

        $content = file_get_contents($file->getPathname());

        $activities = $serializer->deserialize($content, Activity::class . '[]', 'json');

        $errorsArray = [];

        foreach ($activities as $activity) {
            $errors = $validator->validate($activity);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $errorsArray[] = $error->getMessage();
                }
                continue;
            }

            $entityManager->persist($activity);
        }

        $entityManager->flush();

        if (count($errorsArray) > 0) {
            return new JsonResponse(['errors' => $errorsArray], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['message' => 'Activities imported successfully'], Response::HTTP_CREATED);
    }
}
