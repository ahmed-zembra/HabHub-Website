<?php

namespace App\Controller;

use App\Entity\Business;
use App\Entity\Individu;
use App\Entity\Revue;
use App\Form\BusinessType;
use App\Form\RevueType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @Route("/business")
 */
class BusinessController extends AbstractController
{

    /**
     * Creates a new ActionItem entity.
     *
     * @Route("/search-business", name="business_ajax_search")
     * methods={"GET"}
     */
    public function businessSearchAction(Request $request)
    {
        $filters=$request->get("categories");

        $em = $this->getDoctrine()->getManager();

        $requestString = $request->get('m');


        $businesses =  $em->getRepository('App:Business')->findBusinessByName($requestString);
        if(!$businesses) {
            $result['business']['error'] = "not found";
        } else {
            $result['business'] = $this->getRealEntitiesBusinesses($businesses);
        }

        return new Response(json_encode($result));
    }

    public function getRealEntitiesBusinesses($businesses){

        foreach ($businesses as $business){
            $realEntities[$business->getIdBusiness()] = [$business->getIdBusiness(),$business->getImage(),$business->getVille(),$business->getTitre(),$business->getHoraire()];
        }

        return $realEntities;
    }

    /**
     * @Route("/back-office-index", name="app_business_index_back_office", methods={"GET"})
     */
    public function index_back_office(EntityManagerInterface $entityManager): Response
    {

        $businesses = $entityManager
            ->getRepository(Business::class)
            ->findAll();

        return $this->render('business/index.html.twig', [
            'businesses' => $businesses,
        ]);
    }
    /**
     * @Route("/location", name="app_business_index_location", methods={"GET"})
     */
    public function show_location(EntityManagerInterface $entityManager): Response
    {

        return $this->render('business/location.html.twig');
    }
    /**
     * @Route("/", name="app_business_index_n", methods={"GET"})
     */
    public function index(EntityManagerInterface $entityManager): Response
    {

        $businesses = $entityManager
            ->getRepository(Business::class)
            ->findAll();

        return $this->render('business/frontOfficeIndex.html.twig', [
            'businesses' => $businesses,
        ]);
    }
    /**
     * @Route("/filters", name="app_business_index", methods={"GET"})
     */
    public function index_with_filters(EntityManagerInterface $entityManager,Request $request): Response
    {
        $filters=$request->get("categories");
        $businesses = $entityManager
            ->getRepository(Business::class)
            ->getBusinessWithType($filters);

        if($request->get('ajax')){
            return new JsonResponse([
                'content' =>$this->renderView('business/_content.html.twig', [
                    'businesses' => $businesses,
                      ])
                ]);
        }
        $businessez = $entityManager
            ->getRepository(Business::class)
            ->findAll();
        return $this->render('business/frontOfficeIndex.html.twig', [
            'businesses' => $businessez,
        ]);
    }
    /**
     * @Route("/vets", name="app_business_index_vets", methods={"GET"})
     */

    public function show_all_vets(EntityManagerInterface $entityManager): Response
    {
        $businesses = $entityManager
            ->getRepository(Business::class)
            ->findBy(array('type'=>'vet'));
        return $this->render('business/frontOfficeIndex.html.twig', [
            'businesses' => $businesses,
        ]);
    }
    /**
     * @Route("/grooming", name="app_business_index_grooming", methods={"GET"})
     */

    public function show_all_grooming(EntityManagerInterface $entityManager): Response
    {
        $businesses = $entityManager
            ->getRepository(Business::class)
            ->findBusinessWithRating('grooming');
        return $this->render('business/frontOfficeIndex.html.twig', [
            'businesses' => $businesses,
            
        ]);
    }
    /**
     * @Route("/dogsitting", name="app_business_index_dogsitting", methods={"GET"})
     */

    public function show_all_dogsitting(EntityManagerInterface $entityManager): Response
    {
        $businesses = $entityManager
            ->getRepository(Business::class)
            ->findBy(array('type'=>'dogsitting'));
        return $this->render('business/frontOfficeIndex.html.twig', [
            'businesses' => $businesses,
        ]);
    }
    /**
     * @Route("/parks", name="app_business_index_parks", methods={"GET"})
     */

    public function show_all_parks(EntityManagerInterface $entityManager): Response
    {
        $businesses = $entityManager
            ->getRepository(Business::class)
            ->findBy(array('type'=>'parks'));
        return $this->render('business/frontOfficeIndex.html.twig', [
            'businesses' => $businesses,
        ]);
    }

    /**
     * @Route("/new-back-office", name="app_business_new_back_office", methods={"GET", "POST"})
     */
    public function new_back_office(Request $request, EntityManagerInterface $entityManager): Response
    {
        $business = new Business();
        $form = $this->createForm(BusinessType::class, $business);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();
            $fichier = md5(uniqid()) . '.' . $image->guessExtension();
            $image->move(
                $this->getParameter('upload_directory'),
                $fichier
            );
            $business->setImage($fichier);


            $entityManager->persist($business);
            $entityManager->flush();

            return $this->redirectToRoute('app_business_index_back_office', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('business/backOfficeNew.html.twig', [
            'business' => $business,
            'f' => $form->createView(),
        ]);
    }



    /**
     * @Route("/new", name="app_business_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $business = new Business();
        $form = $this->createForm(BusinessType::class, $business);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($business);
            $entityManager->flush();

            return $this->redirectToRoute('app_business_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('business/new.html.twig', [
            'business' => $business,
            'form' => $form->createView(),
        ]);
    }
    /**
     * @Route("/{idbusiness}", name="app_business_show", methods={"POST","GET"})
     */
    public function show(Business $business, Request $request, EntityManagerInterface $entityManager): Response
    {
        $revue = new Revue();
        if ($this->getUser()==null){
            return $this->redirectToRoute('app_login');

        }
        $individu = $entityManager->getRepository(Individu::class)->getIndividuByUser($this->getUser()->getUsername());
        $id=$individu->getIdIndividu();
        $revue->setIdindividu($entityManager
            ->getRepository(Individu::class)
            ->findOneBy(array('idindividu' => $id)));

        $revue->setIdbusiness($entityManager
            ->getRepository(Business::class)
            ->findOneBy(array('idbusiness' => $business->getIdbusiness())));

        $form = $this->createForm(RevueType::class, $revue);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $datepublication = new \DateTime('now');
            $revue->setDatepublication($datepublication);
            $entityManager->persist($revue);
            $entityManager->flush();
            return $this->redirectToRoute('app_business_show', ['idbusiness' => $business->getIdbusiness()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('business/show.html.twig', [
            'business' => $business,
            'form' => $form->createView(),


        ]);
    }
    /**
     * @Route("/{idbusiness}/back-office", name="app_business_show_back_office", methods={"GET"})
     */
    public function show_back_office(Business $business): Response
    {
        return $this->render('business/show_back_office.html.twig', [
            'business' => $business,
        ]);
    }
    /**
     * @Route("/{idbusiness}/edit-back-office", name="app_business_edit_back_office", methods={"GET", "POST"})
     */
    public function edit_back_office(Request $request, Business $business, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BusinessType::class, $business);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_business_index_back_office', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('business/edit.html.twig', [
            'business' => $business,
            'f' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{idbusiness}/edit", name="app_business_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Business $business, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BusinessType::class, $business);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_business_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('business/edit.html.twig', [
            'business' => $business,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{idbusiness}", name="app_business_delete", methods={"POST"})
     */
    public function delete(Request $request, Business $business, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$business->getIdbusiness(), $request->request->get('_token'))) {
            $entityManager->remove($business);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_business_index_back_office', [], Response::HTTP_SEE_OTHER);
    }

    /***********************************JSON METHODS***********************************/
    /**
     * @Route("/mobile/index", name="app_business_index_mobile")
     */
    public function index_mobile(EntityManagerInterface $entityManager)
    {

        $businesses = $entityManager
            ->getRepository(Business::class)
            ->findAll();
        $serializer = new Serializer([new ObjectNormalizer()]);
        $formatted = $serializer->normalize($businesses);

        return new JsonResponse($formatted);
    }

    /**
     * @Route("/show-mobile/show", name="app_business_show_mobile")
     */
    public function show_mobile(Request $request)
    {
        $idbusiness = $request->get("idbusiness");
        $em = $this->getDoctrine()->getManager();
        $business =$em->getRepository(Business::class)
            ->findOneBy(array('idbusiness' => $idbusiness));
        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getIdbusiness();
        });
        $serializer = new Serializer([$normalizer], [$encoder]);
        $formatted = $serializer->normalize($business );
        return new JsonResponse($formatted);
    }

    /**
     * @Route("/mobile/add", name="add_business_mobile")
     *
     */

    public function addBusiness_mobile(Request $request)
    {   $em = $this->getDoctrine()->getManager();
        $iduser =$em->getRepository(Business::class)
            ->findOneBy(array('idbusiness' => '10'))->getIdutilisateur();
        $business = new Business();
        $titre = $request->query->get("titre");
        $description = $request->query->get("description");
        $horaire = $request->query->get("horaire");
        $ville = $request->query->get("ville");
        $type= $request->query->get("type");

        $image= $request->query->get("image");
        $lat= $request->query->get("lat");
        $lng= $request->query->get("lng");


        $em = $this->getDoctrine()->getManager();
        $date = new \DateTime('now');
        $business->setTitre($titre);
        $business->setDescription($description);
        $business->setHoraire($horaire);
        $business->setVille($ville);
        $business->setLocalisation("NotFound");
        $business->setIdutilisateur($iduser);
        $business->setType($type);
        $business->setImage($image);
        $business->setExperience("0");
        $business->setLat($lat);
        $business->setLng($lng);

        $em->persist($business);
        $em->flush();
        $serializer = new Serializer([new ObjectNormalizer()]);
        $formatted = $serializer->normalize($business);
        return new JsonResponse($formatted);

    }
    /**
     * @Route("/delete/mobile", name="delete_business" )
     *
     */

    public function delete_mobile(Request $request) {
        $id = $request->get("id");

        $em = $this->getDoctrine()->getManager();
        $business = $em->getRepository(Business::class)
            ->findOneBy(array('idbusiness' => $id));;
        if($business!=null ) {
            $em->remove($business);
            $em->flush();

            $serialize = new Serializer([new ObjectNormalizer()]);
            $formatted = $serialize->normalize("business a ete supprimé avec success.");
            return new JsonResponse($formatted);

        }
        return new JsonResponse("id business invalide.");


    }
    /**
     * @Route("/update/mobile", name="update_business")
     *
     */
    public function update_mobile(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $business = $this->getDoctrine()->getManager()
            ->getRepository(Business::class)
            ->find($request->get("id"));
        $titre = $request->query->get("titre");
        $description = $request->query->get("description");
        $horaire = $request->query->get("horaire");
        $ville = $request->query->get("ville");
        $type= $request->query->get("type");

        $image= $request->query->get("image");
        $lat= $request->query->get("lat");
        $lng= $request->query->get("lng");


        $em = $this->getDoctrine()->getManager();
        $date = new \DateTime('now');
        $business->setTitre($titre);
        $business->setDescription($description);
        $business->setHoraire($horaire);
        $business->setVille($ville);
        //$business->setLocalisation("NotFound");
        //$business->setIdutilisateur($iduser);
        $business->setType($type);
        $business->setImage($image);
        //$business->setExperience("0");
        $business->setLat($lat);
        $business->setLng($lng);


        $em->persist($business);
        $em->flush();
        $serializer = new Serializer([new ObjectNormalizer()]);
        $formatted = $serializer->normalize($business);
        return new JsonResponse("Reclamation a ete modifiee avec success.");

    }


    /**
     * @Route("/details", name="detail_business")
     *
     */


    public function detailsBusiness(Request $request)
    {
        $id = $request->get("id");

        $em = $this->getDoctrine()->getManager();
        $business = $this->getDoctrine()->getManager()->getRepository(Business::class)->find($id);
        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getDescription();
        });
        $serializer = new Serializer([$normalizer], [$encoder]);
        $formatted = $serializer->normalize($business);
        return new JsonResponse($formatted);
    }

    /************************************END JSON METHODS***********************************/


}
