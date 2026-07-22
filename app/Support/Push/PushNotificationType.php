<?php

namespace App\Support\Push;

class PushNotificationType
{
    public const CHALLENGE_RECEIVED = 'challenge_received';

    public const CHALLENGE_ACCEPTED = 'challenge_accepted';

    public const CHALLENGE_REJECTED = 'challenge_rejected';

    public const COUNTERPROPOSAL_RECEIVED = 'counterproposal_received';

    public const COUNTERPROPOSAL_ACCEPTED = 'counterproposal_accepted';

    public const COUNTERPROPOSAL_REJECTED = 'counterproposal_rejected';

    public const NEW_COMMENT = 'new_comment';

    public const USER_MENTIONED = 'user_mentioned';

    public const NEW_MESSAGE = 'new_message';

    public const TEST = 'test';
}
