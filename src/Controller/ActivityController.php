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

#[Route('/api/activity', name: 'app_activity_')]
class ActivityController extends AbstractController
{
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
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
    public function list(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {

        $activities = $entityManager->getRepository(Activity::class)->findAll();

        $data = $serializer->normalize($activities, null, ['groups' => ['read']]);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/join/{activity}', name: 'join', methods: ['POST'])]
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
}
