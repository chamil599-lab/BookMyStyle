
Hello {{ $booking->name }}*

Your booking status is *pending*.

*Service:* {{ $booking->service->title }}
*Date:* {{ $booking->date->format('F d, Y') }}
*Time:* {{ date('h:i A', strtotime($booking->time)) }}
*Salon:* {{ $booking->salon->name }}

We'll confirm your booking soon.