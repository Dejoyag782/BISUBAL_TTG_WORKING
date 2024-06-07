<?php
namespace App\Services\GeneticAlgorithm;
use DB;
use App\Models\CollegeClass;

class Individual
{
    /**
     * This is the genetic makeup of an individual
     *
     * @var array
     */
    private $chromosome;


    /**
     * Fitness of the individual
     *
     * @var double
     */
    private $fitness;

    private function getRoomIdFromPreference($courseCode)
    {
        // Assuming you have access to database connection
        $queryResult = DB::table('courses')->where('course_code', $courseCode)->first();
        if ($queryResult) {
            // Assuming room_preference is a column in the courses table
            return $queryResult->room_preference;
        } else {
            // Handle case when course code is not found
            return null;
        }
    }



    /**
     * Create a new individual from a timetable
     *
     * @var Timetable The timetable
     */
    public function __construct($timetable = null)
    {

        // Assuming $timetable is an instance of the Timetable class
        // $timeslots = $timetable->getTimeslots();
        // $timeslotsCount = count($timeslots);

        $timeslotLab = false;

        // print "\nLength of Timeslot array: ".$timeslotsCount;

        if ($timetable) {
            $timeslotLab = false;
            $newChromosome = [];
            $chromosomeIndex = 0;

            // First, handle lab courses
            foreach ($timetable->getGroups() as $group) {
                $timeslotLab = false;
                foreach ($group->getModuleIds() as $moduleId) {
                    $module = $timetable->getModule($moduleId);
                    // print "\nOn Module Lab" . $module->getModuleCode() . "\n";
                    

                    // Check if the module code contains "Lab"
                    $isLab = strpos($module->getModuleCode(), "Lab") !== false;

                    if (strpos($module->getModuleCode(), "Lab") !== false) {

                        $hours = $this->extractHours($module->getModuleCode());

                        for ($i = 1; $i <= $module->getSlots($group->getId()); $i++) {                            

                            $timeslotId = $timetable->getRandomTimeslot()->getId();
                            
                            // This is for verifying if timeslot is capable of consecutive slots
                            while($timeslotLab!==true){
                                $timeslotId = $timetable->getRandomTimeslot()->getId();
                                // print"Timeslot ID: ".$timeslotId." [";
                                $timeslotIdCheck = $timeslotId;
                                $timeslotNo = substr($timeslotIdCheck, 3);
                                // print $timeslotNo."-";
                                $maxTimeslot = DB::table('timeslots')->count();
                                // print "Max TS = ".$maxTimeslot."]\n";
                                // if hours is equals to 2
                                if($hours == 2){
                                    if($timeslotNo <= $maxTimeslot-1){
                                        $timeslotLab = true;
                                    }else{
                                        $timeslotLab = false;
                                    }
                                }else if($hours == 3){
                                    if($timeslotNo <= $maxTimeslot-2){
                                        $timeslotLab = true;
                                    }else{
                                        $timeslotLab = false;
                                    }
                                }else if($hours == 4){
                                    if($timeslotNo <= $maxTimeslot-3){
                                        $timeslotLab = true;
                                    }else{
                                        $timeslotLab = false;
                                    }
                                }else if($hours == 5){
                                    if($timeslotNo <= $maxTimeslot-4){
                                        $timeslotLab = true;
                                    }else{
                                        $timeslotLab = false;
                                    }
                                }
                            }

                            // print_r($timeslotId);
                            if ($isLab) {

                                // print "Primary: ";
                            // Add random time slot
                            
                            $newChromosome[$chromosomeIndex] = $timeslotId;
                            $chromosomeIndex++;
                            // print $timeslotId.",";


                            // Add random room if room preference is not available
                            $courseCode = $module->getModuleCode(); // Assuming module code represents course code
                                if ($this->getRoomIdFromPreference($courseCode)!==null){
                                    $roomId = $this->getRoomIdFromPreference($courseCode);
                                }else{
                                    // $roomId = $timetable->getRandomRoom()->getId();

// -------------------------------------------------------------------- Add random room -------------------------------------------------------------------------------------------
                                    // Retrieve the class information based on group ID
                                    $classRoom = CollegeClass::where('id', $group->getId())->first();  // Assuming 'group_id' is the relevant column in your database

                                    if ($classRoom) {
                                        // Access the available_rooms column
                                        $jsonString  = $classRoom->available_rooms;
                                        $arrayOfIntegers = array_map('intval', json_decode($jsonString, true));
                                        // Get a random key from the array
                                        $randomKey = array_rand($arrayOfIntegers);
                                        // Use the random key to get the corresponding value
                                        $randomRoomSelected = $arrayOfIntegers[$randomKey];
                                        // print_r($arrayOfIntegers);
                                    }
                                    $roomId = $randomRoomSelected;
                                }
                            $newChromosome[$chromosomeIndex] = $roomId;
                            $chromosomeIndex++;
                            // print $roomId.",";
                            
                            // Add random room
                            // $roomId = $timetable->getRandomRoom()->getId();
                            // $newChromosome[$chromosomeIndex] = $roomId;
                            // $chromosomeIndex++;

                            // Add random professor
                            $professor = $module->getRandomProfessorId();
                            $newChromosome[$chromosomeIndex] = $professor;
                            $chromosomeIndex++;
                            // print $professor."\n";

                            
                                for ($j = 0; $j < $hours-1; $j++) {
        
                                    // print "Subslot: ";
                                    $timeslotId = $timetable->getTimeslot($timeslotId)->getNext();
                                    // $timeslotId = $timetable->getRandomTimeslot()->getId();
                                    $newChromosome[$chromosomeIndex] = $timeslotId;
                                    $chromosomeIndex++;
                                    // print $timeslotId.",";
        
                                    $newChromosome[$chromosomeIndex] = $roomId;
                                    $chromosomeIndex++;
                                    // print $roomId.",";
        
                                    $newChromosome[$chromosomeIndex] = $professor;
                                    $chromosomeIndex++;
                                    // print $professor."\n";

                                    $module->increaseAllocatedSlots();
                                }
                            }
                           

                            $module->increaseAllocatedSlots();
                            // $timeslot = $timetable->getTimeslot($timeslotId);

                            // $timeslotId = $timeslot->getNext();
                            // while (($i + 1) <= $timetable->maxContinuousSlots && ($module->getSlots() != $module->getAllocatedSlots()) && ($timeslotId > -1)) {
                            //     $newChromosome[$chromosomeIndex] = $timeslotId;
                            //     $chromosomeIndex++;

                            //     $newChromosome[$chromosomeIndex] = $roomId;
                            //     $chromosomeIndex++;

                            //     $newChromosome[$chromosomeIndex] = $professor;
                            //     $chromosomeIndex++;

                            //     $timeslotId = $timetable->getTimeslot($timeslotId)->getNext();
                            //     $module->increaseAllocatedSlots();
                            //     $i += 1;
                            // }
                            $timeslotLab = false;
                        }
                    }else{
                        $timeslotLab = false;
                        if (strpos($module->getModuleCode(), "Lab") !== true) {
                            $timeslotLab = false;

                            $hours = $this->extractHours($module->getModuleCode());
    
                            for ($i = 1; $i <= $module->getSlots($group->getId()); $i++) {
    
                                if (!$isLab) {
    
                                    // print "Primary: ";
                                // Add random time slot
                                $timeslotId = $timetable->getRandomTimeslot()->getId();
                                $newChromosome[$chromosomeIndex] = $timeslotId;
                                $chromosomeIndex++;
                                // print $timeslotId.",";
    
// -------------------------------------------------------------------- Add random room --------------------------------------------------------------------------------------------
                                // Retrieve the class information based on group ID
                                $classRoom = CollegeClass::where('id', $group->getId())->first();  // Assuming 'group_id' is the relevant column in your database

                                if ($classRoom) {
                                    // Access the available_rooms column
                                    $jsonString  = $classRoom->available_rooms;
                                    $arrayOfIntegers = array_map('intval', json_decode($jsonString, true));
                                    // Get a random key from the array
                                    $randomKey = array_rand($arrayOfIntegers);
                                    // Use the random key to get the corresponding value
                                    $randomRoomSelected = $arrayOfIntegers[$randomKey];
                                    // print_r($arrayOfIntegers);
                                }
                                $roomId = $randomRoomSelected;
                                // print"CheckRoomID:".$roomId."\n";
                                $newChromosome[$chromosomeIndex] = $roomId;
                                $chromosomeIndex++;
                                // print $roomId.",";
    
                                // Add random professor
                                $professor = $module->getRandomProfessorId();
                                $newChromosome[$chromosomeIndex] = $professor;
                                $chromosomeIndex++;
                                // print $professor."\n";
    
                                }
    
                                
                                $module->increaseAllocatedSlots();
                                $timeslot = $timetable->getTimeslot($timeslotId);
    
                                $timeslotId = $timeslot->getNext();
                                while (($i + 1) <= $timetable->maxContinuousSlots && ($module->getSlots() != $module->getAllocatedSlots()) && ($timeslotId > -1)) {
                                    $newChromosome[$chromosomeIndex] = $timeslotId;
                                    $chromosomeIndex++;
    
                                    $newChromosome[$chromosomeIndex] = $roomId;
                                    $chromosomeIndex++;
    
                                    $newChromosome[$chromosomeIndex] = $professor;
                                    $chromosomeIndex++;
    
                                    $timeslotId = $timetable->getTimeslot($timeslotId)->getNext();
                                    $module->increaseAllocatedSlots();
                                    $i += 1;
                                }
                            }
                        }
                    }
                }
            }


            foreach ($timetable->getModules() as $module) {
                $module->resetAllocated();
            }
        } else {
            $newChromosome = [];
        }

        
        // for displaying chromosome
        // $prntNC = $newChromosome;
        // print_r($prntNC);
        $this->chromosome = $newChromosome;
    }

    private function extractHours($moduleCode)
    {
        // Extract number of hours using regex
        if (preg_match('/(\d+)hr/', $moduleCode, $matches)) {
            return (int)$matches[1];
        }

        return 0; // Default to 0 if no match found
    }


    /**
     * Create a new individual with a randomised chromosome
     *
     * @param int $chromosomeLength Desired chromosome length
     */
    public static function random($chromosomeLength)
    {
        // print "\nNew Individuals  (Chromosome Length:".$chromosomeLength.")\n";
        $individual = new Individual();
        // print "\n-------------------------------------------------------------Start New Indiv-------------------------------------------------------------\n";

        for ($i = 0; $i < $chromosomeLength; $i++) {
            $individual->setGene($i, mt_rand(0, 1));
        }

        return $individual;
    }

    /**
     * Get the individual's chromosome
     *
     * @return array The chromosome
     */
    public function getChromosome()
    {
        return $this->chromosome;
    }

    /**
     * Get the length of the individual's chromosome
     *
     * @return int The length
     */
    public function getChromosomeLength()
    {
        return count($this->chromosome);
    }

    /**
     * Fix a gene at the given location of the chromosome
     *
     * @param int $index The location to insert the gene
     * @param int $gene The gene
     */
    public function setGene($index, $gene)
    {
        $this->chromosome[$index] = $gene;
    }

    /**
     * Get the gene at the specified location
     *
     * @param $index The location to get the gene at
     * @return int The bit representing the gene at that location
     */
    public function getGene($index)
    {
        return $this->chromosome[$index];
    }

    /**
     * Set the fitness param for this individual
     *
     * @param double $fitness The fitness of this individual
     */
    public function setFitness($fitness)
    {
        $this->fitness = $fitness;
    }

    /**
     * Get the fitness for this individual
     *
     * @return double The fitness of the individual
     */
    public function getFitness()
    {
        return $this->fitness;
    }

    /**
     * Get a printout of the individual
     *
     * @return string Output of the individual details
     */
    public function __toString()
    {
        return $this->getChromosomeString();
    }

    public function getChromosomeString()
    {
        return implode(",", $this->chromosome);
    }
}