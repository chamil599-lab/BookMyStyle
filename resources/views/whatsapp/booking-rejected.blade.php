
Hello {{ $booking->name }}*

Unfortunately, your booking for *{{ $booking->service->title }}* has been *rejected*.

*Date:* {{ $booking->date->format('F d, Y') }}
*Time:* {{ date('h:i A', strtotime($booking->time)) }}

Please contact us for more details or book another slot.