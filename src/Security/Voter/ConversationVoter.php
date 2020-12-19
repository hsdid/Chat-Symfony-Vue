<?php 

namespace App\Security\Voter;
use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

//dont give permision to see conversation if you are not participant

class ConversationVoter extends Voter{
    
    /**
     * @var $conversationRepository;
     */
    private $conversationRepository;

    public function __construct(ConversationRepository $conversationRepository){

        $this->conversationRepository = $conversationRepository;
    }


    const VIEW = 'view';

    protected function supports(string $attribute, $subject)
    {
        //dd($attribute, $subject);
        return $attribute == self::VIEW && $subject instanceof Conversation;
    }


    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        $result = $this->conversationRepository->checkIfUserisParticipant(
            $subject->getId(),
            $token->getUser()->getId()
        );

    
        return !!$result;
           
    }
}


