<?php

// namespace App\Http\Controllers\Api\V1;

// use App\Http\Controllers\Controller;
// use Mailjet\LaravelMailjet\Facades\Mailjet;

// class MailjetController extends Controller
// {
//     public function test()
//     {
//         $email = 'ton@email.com';
//         $code = '123456';

//         $body = [
//             'Messages' => [
//                 [
//                     'From' => [
//                         'Email' => env('MAIL_FROM_ADDRESS', 'vonnemary19@gmail.com'),
//                         'Name' => env('MAIL_FROM_NAME', 'Banque Vonne')
//                     ],
//                     'To' => [
//                         ['Email' => $email]
//                     ],
//                     'Subject' => "Test Mailjet",
//                     'TextPart' => "Votre code est : $code",
//                     'HTMLPart' => "<h3>Votre code est : <strong>$code</strong></h3>"
//                 ]
//             ]
//         ];

//         Mailjet::send($body);

//         return response()->json(['message' => 'Mail envoyé']);
//     }
// }
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Mailjet\LaravelMailjet\Facades\Mailjet;

class MailjetController extends Controller
{
    public function test()
    {
        $email = 'ton@email.com';
        $code = '123456';

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => env('MAIL_FROM_ADDRESS', 'vonnemary19@gmail.com'),
                        'Name' => env('MAIL_FROM_NAME', 'Banque Vonne')
                    ],
                    'To' => [
                        ['Email' => $email]
                    ],
                    'Subject' => "Test Mailjet",
                    'TextPart' => "Votre code est : $code",
                    'HTMLPart' => "<h3>Votre code est : <strong>$code</strong></h3>"
                ]
            ]
        ];

        Mailjet::send($body);

        return response()->json(['message' => 'Mail envoyé']);
    }
}
