<?php

require_once __DIR__ . '/../packet_cases.php';

function test_receive_mci()
{
    $a = test_packet_make('CompCar');
    $b = test_packet_make('CompCar');
    $bytes = pack('CCCC', 4 + 2 * 28, ISP_MCI, 1, 2) . $a->pack() . $b->pack();

    $packet = new IS_MCI($bytes);
    assertSame(60, $packet->Size);
    assertSame(2, $packet->NumC);
    assertSame(2, count($packet->Info));
    assertSame($a->PLID, $packet->Info[0]->PLID);
    assertSame($b->PLID, $packet->Info[1]->PLID);
}

function test_receive_nlp()
{
    $a = test_packet_make('NodeLap');
    $b = test_packet_make('NodeLap');
    $bytes = pack('CCCC', 4 + 2 * 6, ISP_NLP, 0, 2) . $a->pack() . $b->pack();

    $packet = new IS_NLP($bytes);
    assertSame(16, $packet->Size);
    assertSame(2, $packet->NumP);
    assertSame(2, count($packet->Info));
    assertSame($a->PLID, $packet->Info[0]->PLID);
    assertSame($b->PLID, $packet->Info[1]->PLID);
}

function test_receive_con()
{
    $a = test_packet_make('CarContact');
    $b = test_packet_make('CarContact');
    $bytes = pack('CCCCvv', 40, ISP_CON, 0, 0, 100, 200) . $a->pack() . $b->pack();

    $packet = new IS_CON($bytes);
    assertSame(40, $packet->Size);
    assertSame(100, $packet->SpClose);
    assertSame(200, $packet->Time);
    assertSame($a->PLID, $packet->A->PLID);
    assertSame($b->PLID, $packet->B->PLID);
    assertSame(10, (int) $packet->getClosingSpeed());
}

function test_receive_hcp()
{
    $body = '';
    for ($i = 0; $i < 32; ++$i) {
        $car = new CarHCP();
        $car->H_Mass = $i;
        $car->H_TRes = 200 - $i;
        $body .= $car->pack();
    }
    $packet = new IS_HCP(pack('CCCC', 68, ISP_HCP, 0, 0) . $body);

    assertSame(68, $packet->Size);
    assertSame(32, count($packet->Info));
    assertSame(0, $packet->Info[0]->H_Mass);
    assertSame(31, $packet->Info[31]->H_Mass);
    assertSame(169, $packet->Info[31]->H_TRes);
}

function test_receive_hos()
{
    $host1 = pack('a32a6CC', 'Host One', 'BL1', 1, 2);
    $host2 = pack('a32a6CC', 'Host Two', 'SO1', 3, 4);
    $bytes = pack('CCCC', 84, IRP_HOS, 5, 2) . $host1 . $host2;

    $packet = new IR_HOS($bytes);
    assertSame(84, $packet->Size);
    assertSame(2, $packet->NumHosts);
    assertSame(2, count($packet->Info));
    assertSame('Host One', $packet->Info[0]->HName);
    assertSame('BL1', $packet->Info[0]->Track);
    assertSame(4, $packet->Info[1]->NumConns);
}

function test_receive_outsim()
{
    $bytes = pack('V', 123)
           . pack('f3', 1, 2, 3)
           . pack('f', 4)
           . pack('f', 5)
           . pack('f', 6)
           . pack('f3', 7, 8, 9)
           . pack('f3', 10, 11, 12)
           . pack('V3', 13, 14, 15);

    assertSame(OutSimPack::LENGTH, strlen($bytes));
    $packet = new OutSimPack($bytes);
    assertSame(123, $packet->Time);
    assertSame(1.0, $packet->AngVel[0]);
    assertSame(4.0, $packet->Heading);
    assertSame(7.0, $packet->Accel[0]);
    assertSame(10.0, $packet->Vel[0]);
    assertSame(13, $packet->Pos[0]);
}

return array(
    'receive/mci'    => 'test_receive_mci',
    'receive/nlp'    => 'test_receive_nlp',
    'receive/con'    => 'test_receive_con',
    'receive/hcp'    => 'test_receive_hcp',
    'receive/hos'    => 'test_receive_hos',
    'receive/outsim' => 'test_receive_outsim',
);
