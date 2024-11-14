<?php

namespace App\Controller;

use App\DTO\CourseDTO;
use App\Entity\Course;
use App\Exception\BillingUnavailableException;
use App\Form\CourseType;
use App\Helper\ArrayColumnToKeyHelper;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/courses")]
class CourseController extends AbstractController
{
    private BillingClient $billingClient;
    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }

    #[Route("/", name: "app_course_index", methods: ["GET"])]
    public function index(CourseRepository $courseRepository): Response
    {
        $user = $this->getUser();
        $billingTransactions = null;
        if ($user) {
            $billingTransactions = $this->billingClient->getTransactions(
                $user->getApiToken(),
                'Payment',
                true,
            );
        }

        $billingCourses = ArrayColumnToKeyHelper::mapToKey(
            $this->billingClient->getCourses(),
            'character_code'
        );


        $courses = ArrayColumnToKeyHelper::mapToKey($courseRepository->findAllToArray(), 'character_code');

        foreach ($courses as $key => &$course) {
            $course['type'] = $billingCourses[$key]['type'];
            $course['price'] = $billingCourses[$key]['price'] ?? null;
        }


        return $this->render("course/index.html.twig", [
            "courses" => $courses,
            "billingTransactions" => json_decode($billingTransactions, JSON_OBJECT_AS_ARRAY),
        ]);
    }

    #[Route("/new", name: "app_course_new", methods: ["GET", "POST"])]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $courseDTO = (new CourseDTO())
                ->setType(\App\Enum\CourseType::tryFrom($course->getType())->getName())
                ->setTitle($course->getTitle())
                ->setPrice($course->getPrice())
                ->setCharacterCode($course->getCharacterCode());

            $this->billingClient->createCourse($courseDTO, $this->getUser());
            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute(
                "app_course_index",
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render("course/new.html.twig", [
            "course" => $course,
            "form" => $form,
        ]);
    }

    #[Route("/{id}", name: "app_course_show", methods: ["GET"])]
    public function show(Course $course): Response
    {
        $lessons = $course->getLessons()->getValues();
        $user = $this->getUser();
        $billingUser = null;
        $isCoursePaid = false;
        $transactions = null;
        $billingCourse = null;
        if ($user) {
            try {
                $billingCourse = $this->billingClient->getCourseByCode($course->getCharacterCode());
                $billingCourse['type'] = \App\Enum\CourseType::tryFrom($billingCourse['type'])->getName();
                $billingUser = $this->billingClient->currentUser($user->getApiToken());
                $isCoursePaid = $this->billingClient->courseIsPaid($course->getCharacterCode(), $user);
                $transactions = $this->billingClient->getTransactions(
                    $course->getCharacterCode(),
                    courseCode: $user->getApiToken()
                );
            } catch (\Exception $exception) {
                throw new BillingUnavailableException();
            }
        }

        return $this->render("course/show.html.twig", [
            "course" => $course,
            "lessons" => $lessons,
            "billingCourse" => $billingCourse,
            "isCoursePaid" => $isCoursePaid,
            "billingUser" => $billingUser,
            "transactions" => $transactions
        ]);
    }

    #[Route("/{id}/edit", name: "app_course_edit", methods: ["GET", "POST"])]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function edit(
        Request $request,
        Course $course,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $courseDTO = (new CourseDTO())
                ->setType(\App\Enum\CourseType::tryFrom($course->getType())->getName())
                ->setTitle($course->getTitle())
                ->setPrice($course->getPrice())
                ->setCharacterCode($course->getCharacterCode());

            $this->billingClient->editCourse($courseDTO, $this->getUser());
            $entityManager->flush();

            return $this->redirectToRoute(
                "app_course_index",
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render("course/edit.html.twig", [
            "course" => $course,
            "form" => $form,
        ]);
    }

    #[Route("/{id}", name: "app_course_delete", methods: ["POST"])]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function delete(
        Request $request,
        Course $course,
        EntityManagerInterface $entityManager
    ): Response {
        if (
            $this->isCsrfTokenValid(
                "delete" . $course->getId(),
                $request->getPayload()->getString("_token")
            )
        ) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute(
            "app_course_index",
            [],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route('/{id}/pay', name: 'app_course_pay', methods: ['GET'])]
    public function pay(
        Course $course,
        BillingClient $billingClient
    ): RedirectResponse {

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        try {
            $response = $billingClient->pay($user->getApiToken(), $course->getCharacterCode());
            if (isset($response['success']) && $response['success']) {
                $this->addFlash(
                    'success',
                    'Курс успешно приобретен!'
                );
            } else {
                $this->addFlash(
                    'error',
                    $response['message']
                );
            }
        } catch (BillingUnavailableException $e) {
            $this->addFlash(
                'error',
                $e->getMessage()
            );
        }
        return $this->redirectToRoute('app_course_index');
    }
}
