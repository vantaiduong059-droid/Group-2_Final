<?php
// app/Patterns/Observer/SubjectInterface.php

interface SubjectInterface {
    public function attach(ObserverInterface $observer);
    public function detach(ObserverInterface $observer);
    public function notify($eventData);
}
