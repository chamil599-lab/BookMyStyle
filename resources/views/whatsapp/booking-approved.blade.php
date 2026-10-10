
Hello {{ $booking->name }}*

Your booking for *{{ $booking->service->title }}* has been *approved*.

*Date:* {{ $booking->date->format('F d, Y') }}
*Time:* {{ date('h:i A', strtotime($booking->time)) }}
*Salon:* {{ $booking->salon->name }}

Thank you for booking with us! 😊