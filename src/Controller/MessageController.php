<?php

namespace App\Controller;



use App\Entity\Message;
use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;

/**
* @Route("/messages", name="messages.")
*/

class MessageController extends AbstractController
{

    
    const ATTRIBUTES_TO_SERIALIZE = ['id', 'content', 'createdAt', 'mine'];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var MessageRepository
     */
    private $messageRepository;

    public function __construct(EntityManagerInterface $entityManager,MessageRepository $messageRepository, UserRepository $userRepository, ParticipantRepository $participantRepository, PublisherInterface $publisher) {

        $this->entityManager         = $entityManager;
        $this->messageRepository     = $messageRepository;
        $this->userRepository        = $userRepository;
        $this->participantRepository = $participantRepository;
        $this->publisher             = $publisher;
    }


    /**
     * @Route("/{id}", name="getMessages", methods={"GET"})
     * 
     * @param Request $request
     * @return Response
     */
    public function index(Request $request, ConversationRepository $conversationRepository, $id)
    {
        
        $conversation = $conversationRepository->findOneByid($id);
        //can i view the conversation

        $this->denyAccessUnlessGranted('view', $conversation);
        
        $messages = $this->messageRepository->findMessageByConversationId(
            $conversation->getId()
        );

        /**
         * @var $message Message
         */

        //set mine true if message user id is eq to logged user id
        array_map(function($message) {
            $message->setMine(
                $message->getUser()->getId() === $this->getUser()->getId() ? true : false
            );
        }, $messages);

        return $this->json($messages, Response::HTTP_OK, [], [
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }


    /**
     * @Route("/{id}", name="newMessages", methods={"POST"})
     * 
     * @param Request $request
     * @return Response
     * @param SerializerInterface $serializer
     * @throws \Exception
     */
    public function newMessage(Request $request, ConversationRepository $conversationRepository, $id,  SerializerInterface $serializer) {
        
        $user   = $this->getUser();
        $conversation = $conversationRepository->findOneByid($id);


        $recipient = $this->participantRepository->findParticipantByConversationIdAndUserId(
            $conversation->getId(),
            $user->getId()
        );

        
        $content = $request->get('content', null); 
        $message = new Message();
        $message->setContent($content);
        $message->setUser($user);
        

        $conversation->addMessage($message);
        $conversation->setLastMessage($message);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->entityManager->persist($message);
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();
            $this->entityManager->commit();

        }catch (\Expection $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $message->setMine(false);
        $messageSerialized = $serializer->serialize($message, 'json', [
            'attributes' => ['id', 'content', 'createdAt', 'mine', 'conversation' => ['id']]
        ]);

        $update = new Update(
            [
                sprintf("/conversations/%s", $conversation->getId()),
                sprintf("/conversations/%s", $recipient->getUser()->getUsername())
            ],
            $messageSerialized,
            [           
                sprintf("/%s", $recipient->getUser()->getUsername())
            ]
        );

        $this->publisher->__invoke($update);

        $message->setMine(true);

        return $this->json($message, Response::HTTP_CREATED, [], [
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);

    }

    
}
