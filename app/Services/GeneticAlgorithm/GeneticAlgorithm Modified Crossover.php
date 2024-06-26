<?php

namespace App\Services\GeneticAlgorithm;

class GeneticAlgorithm
{
    /**
     * This is the number of individuals in the population
     *
     * @var int
     */
    private $populationSize;

    /**
     * This is the probability in which a specific gene in a solution’s
     * chromosome will be mutated
     *
     * @var double
     */
    private $mutationRate;

    /**
     * This is the frequency in which crossover is applied
     *
     * @var double
     */
    private $crossoverRate;

    /**
     * This represents the number of individuals to be
     * considered as elite and skipped during crossover
     *
     * @var integer
     */
    private $elitismCount;

    /**
     * Size of the tournament
     *
     * @var int
     */
    private $tournamentSize;

    /**
     * Temperature for simulated annealing
     *
     * @var int
     */
    private $temperature;

    /**
     * Cooling rate for simulated annealing
     *
     * @var int
     */
    private $coolingRate;

    /**
     * Create a new instance of this class
     */
    public function __construct($populationSize, $mutationRate, $crossOverRate, $elitismCount, $tournamentSize)
    {
        $this->populationSize = $populationSize;
        $this->mutationRate = $mutationRate;
        $this->crossoverRate = $crossOverRate;
        $this->elitismCount = $elitismCount;
        $this->tournamentSize = $tournamentSize;
        $this->temperature = 1.0;
        $this->coolingRate = 0.001;
    }

    /**
     * Initialize a population
     *
     * @param Timetable $timetable Timetable for generating individuals
     */
    public function initPopulation($timetable)
    {
        $population = new Population($this->populationSize, $timetable);

        return $population;
    }

    /**
     * Get the temperature
     */
    public function getTemperature()
    {
        return $this->temperature;
    }

    /**
     * Cool temperature
     *
     */
    public function coolTemperature()
    {
        $this->temperature *= (1 - $this->coolingRate);
    }

    /**
     * Calculate the fitness of a given individual
     *
     * @param Individual $individual The individual
     * @param \App\Services\GeneticAlgorithm\Timetable $timetable A timetable
     * @return double The fitness of the individual
     */
    public function calculateFitness($individual, $timetable)
    {
        $timetable = clone $timetable;

        $timetable->createClasses($individual);
        $clashes = $timetable->calcClashes();
        $fitness = 1.0 / ($clashes + 1);

        $individual->setFitness($fitness);
        return $fitness;
    }

    /**
     * Evaluate a given population
     *
     * @param Population $population The population to evaluate
     * @param Timetable $timetable Timetable data
     */
    public function evaluatePopulation($population, $timetable)
    {
        $populationFitness = 0;

        $individuals = $population->getIndividuals();

        print "\n-----------------------------------------------------------------------Counting Clashes-----------------------------------------------------------------------\n";
        print "\nNo of Clashes:\n";
        foreach ($individuals as $individual) {
            $populationFitness += $this->calculateFitness($individual, $timetable);
        }

        $population->setPopulationFitness($populationFitness);
    }

    /**
     * Determine whether the termination condition has been met
     * For this problem, this occurs when we get an individual with
     * a fitness of 1.0
     *
     * @param Population $population Population we are evaluating
     * @return boolean The truth value of this check
     */
    public function isTerminationConditionMet($population)
    {
        return $population->getFittest(0)->getFitness() == 1;
    }

    /**
     * Determine whether we have reached the max generations we want to
     * iterate through
     *
     * @param int $generations Number of generations
     * @param int $maxGenerations Max generations
     */
    public function isGenerationsMaxedOut($generations, $maxGenerations)
    {
        return $generations > $maxGenerations;
    }

    /**
     * Select a parent from a population to be used in a crossover
     * with some other individual
     *
     * The technique used here is tournament selection method
     *
     * @param Population $population The population
     * @return Individual The selected parent
     */
    public function selectParent($population)
    {
        $tournament = new Population();

        $population->shuffle();

        for ($i = 0; $i < $this->tournamentSize; $i++) {
            $participant = $population->getIndividual($i);
            $tournament->setIndividual($i, $participant);
        }

        return $tournament->getFittest(0);
    }

    /**
     * Perform  a crossover on a population's individuals
     *
     * @param Population $population The population
     * @return Population $newPopulation The resulting population
     */
    public function crossoverPopulation($population)
    {
        $newPopulation = new Population($population->size());
    
        for ($i = 0; $i < $population->size(); $i++) {
            $parentA = $population->getFittest($i);
    
            // Debugging output
            // print "\n\nParentA: ".$parentA."\n";
    
            $random = mt_rand() / mt_getrandmax();
    
            // Debugging output
            // print "\n(?".$this->crossoverRate.">".$random."):(interval=".$i.") ";
    
            if (($this->crossoverRate > $random) && ($i > $this->elitismCount)) {
                // Create offspring
                $offspring = Individual::random($parentA->getChromosomeLength());
    
                // Debugging output
                // print "\n\nPopulating with crossover:\n";
                // print "Offspring: ".$offspring."\n";
    
                $parentB = $this->selectParent($population);
    
                $swapPoint = mt_rand(0, $parentB->getChromosomeLength()-1);
                // print "SwapPoint = ".$swapPoint."\n";

                // --------------------------------- Start : Swap Point Duration Checker -------------------------------- //

                    $gene = $parentA->getGene($swapPoint);
                    $geneCheck = $gene;
                    
                        // Regular expression pattern to match D#T# format
                    $pattern = '/^D\d+T\d+$/';

                    // // Perform the regex match
                    if (preg_match($pattern, $geneCheck) && ($swapPoint > 0 && $swapPoint < $parentA->getChromosomeLength()-1)){

                        // print "Timeslot:".$gene."\n";
                        $day = substr($gene, 1, 1); // Get the character at index 1 (0-based index)
                        $timeslot = substr($gene, 3, 1); // Get the character at index 3 (0-based index)

                        // Convert the extracted characters to integers
                        $day = intval($day);
                        $timeslot = intval($timeslot);

                        // echo "Gene".$swapPoint."[";
                        // echo "D".$day."T".$timeslot.",";
                        
                        $roomId = $parentA->getGene($swapPoint+1);
                        $profId = $parentA->getGene($swapPoint+2);

                        // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                        if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                $parentA->getGene($swapPoint-3) == "D$day"."T".($timeslot-1) && ($swapPoint != 0)){
                                $swapPoint = $swapPoint-3;
                                // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                $parentA->getGene($swapPoint-3) == "D$day"."T".($timeslot-1)){
                                    $swapPoint = $swapPoint-3;
                                    // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                    if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                    $parentA->getGene($swapPoint-3) == "D$day"."T".($timeslot-1)){
                                        $swapPoint = $swapPoint-3;
                                        // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                        if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                        $parentA->getGene($swapPoint-3) == "D$day"."T".($timeslot-1)){                                            
                                            $swapPoint = $swapPoint-3;
                                            // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                            if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                            $parentA->getGene($swapPoint-3) == "D$day"."T".($timeslot-1)){                                                
                                                $swapPoint = $swapPoint-3;
                                                // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                            }
                                        }
                                    }
                                }
                            }

                    }
                    else{
                        $geneCheckPoint = $swapPoint;
                        $gene = $parentA->getGene($geneCheckPoint);
                        // print "Not Timeslot:".$swapPoint." = ".$gene."\n";
                        $geneCheck = $gene;
                        $pattern = '/^D\d+T\d+$/';

                        // print"1stCheckForTimeslot:".$geneCheck."\n";
                        $swapPoint = $geneCheckPoint;

                        if(!preg_match($pattern, $geneCheck)){
                            if($geneCheckPoint-1 > 0){
                                $geneCheckPoint2 = $geneCheckPoint-1;
                            }else{
                                $geneCheckPoint2 = $geneCheckPoint;
                            } 
                           
                            
                            $gene2 = $parentA->getGene($geneCheckPoint2);
                            $geneCheck2 = $gene2;
                            $pattern = '/^D\d+T\d+$/';

                            // print"2ndCheckForTimeslot:".$geneCheck2."\n";
                            $swapPoint = $geneCheckPoint2;

                            if(!preg_match($pattern, $geneCheck2)){      
                                if($geneCheckPoint2 > 0){
                                    $geneCheckPoint3 = $geneCheckPoint2-1;
                                }else if($geneCheckPoint2 == 0){
                                    $geneCheckPoint3 = $geneCheckPoint2;
                                }                                                      
                                $gene3 = $parentA->getGene($geneCheckPoint3);
                                $geneCheck3 = $gene3;
                                $pattern = '/^D\d+T\d+$/';
                                
                                // print"3rdCheckForTimeslot:".$geneCheck3."\n";
                                $swapPoint = $geneCheckPoint3;

                                if(preg_match($pattern, $geneCheck3) && $geneCheckPoint3 > 0){      
                                    $geneCheckPointFinale = $geneCheckPoint3;                      
                                    $geneFinale = $parentA->getGene($geneCheckPointFinale);
                                    $geneCheckFinale = $geneFinale;
                                    // print"FinaleCheckForTimeslot:".$geneCheckFinale."\n";
                                    
                                    $pattern = '/^D\d+T\d+$/';    
                                    // Add iteration logic here to check for duration slots behind it
                                    $day = substr($geneCheck3, 1, 1); // Get the character at index 1 (0-based index)
                                    $timeslot = substr($geneCheck3, 3, 1); // Get the character at index 3 (0-based index)

                                    // Convert the extracted characters to integers
                                    $day = intval($day);
                                    $timeslot = intval($timeslot);

                                    // echo "Gene".$swapPoint."[";
                                    // echo "D".$day."T".$timeslot.",";
                                    
                                    $roomId = $parentA->getGene($geneCheckPointFinale+1);
                                    $profId = $parentA->getGene($geneCheckPointFinale+2);
                                    $swapPoint = $geneCheckPointFinale;

                                    // print"TimeslotIncrementCheck: ".$parentA->getGene($geneCheckPointFinale-3)."->".$parentA->getGene($geneCheckPointFinale)." ?\n";
                                    if($roomId == $parentA->getGene($geneCheckPointFinale-2) && $profId == $parentA->getGene($geneCheckPointFinale-1) &&
                                        $parentA->getGene($geneCheckPointFinale-3) == "D$day"."T".($timeslot-1) && ($geneCheckPointFinale != 0)){
                                        $swapPoint = $geneCheckPointFinale-3;
                                        // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                        if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                        $parentA->getGene($swapPoint-3) == "D$day"."T".($timeslot-1) && ($swapPoint != 0)){
                                            $swapPoint = $swapPoint-3;
                                            // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                            if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                            $parentA->getGene($swapPoint-3) == "D$day"."T".($timeslot-1) && ($swapPoint != 0)){
                                                $swapPoint = $swapPoint-3;
                                                // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                                if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                                $parentA->getGene($geneCheckPointFinale-3) == "D$day"."T".($timeslot-1) && ($swapPoint != 0)){                                            
                                                    $swapPoint = $swapPoint-3;
                                                    // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                                    if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                                    $parentA->getGene($swapPoint-3) == "D$day"."T".($timeslot-1) && ($swapPoint != 0)){                                                
                                                        $swapPoint = $swapPoint-3;
                                                        // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                                    } else if($swapPoint == 0){
                                                        continue;
                                                    }
                                                } else if($swapPoint == 0){
                                                    continue;
                                                }
                                            } else if($swapPoint == 0){
                                                continue;
                                            }
                                        } else if($swapPoint == 0){
                                            continue;
                                        }
                                    } else if($geneCheckPointFinale == 0){
                                        continue;
                                    }

                                }else if(preg_match($pattern, $geneCheck3) && $geneCheckPoint3 == 0){
                                    $swapPoint = $geneCheckPoint3;
                                }

                            } else {                                
                                     
                                    $geneCheckPointFinale = $geneCheckPoint2;                      
                                    $geneFinale = $parentA->getGene($geneCheckPointFinale);
                                    $geneCheckFinale = $geneFinale;
                                    // print"FinaleCheckForTimeslot:".$geneCheckFinale."\n";
                                    $pattern = '/^D\d+T\d+$/';    
                                    // Add iteration logic here to check for duration slots behind it
                                    $day = substr($geneCheck2, 1, 1); // Get the character at index 1 (0-based index)
                                    $timeslot = substr($geneCheck2, 3, 1); // Get the character at index 3 (0-based index)

                                    // Convert the extracted characters to integers
                                    $day = intval($day);
                                    $timeslot = intval($timeslot);

                                    // echo "Gene".$swapPoint."[";
                                    // echo "D".$day."T".$timeslot.",";
                                    
                                    $roomId = $parentA->getGene($geneCheckPointFinale+1);
                                    $profId = $parentA->getGene($geneCheckPointFinale+2);
                                    $swapPoint = $geneCheckPointFinale;

                                    // print"TimeslotIncrementCheck: ".$parentA->getGene($geneCheckPointFinale-3)."->".$parentA->getGene($geneCheckPointFinale)." ?\n";
                                    if($roomId == $parentA->getGene($geneCheckPointFinale-2) && $profId == $parentA->getGene($geneCheckPointFinale-1) &&
                                        $parentA->getGene($geneCheckPointFinale-3) == "D$day"."T".($timeslot-1) && ($geneCheckPointFinale != 0)){
                                        $swapPoint = $geneCheckPointFinale-3;
                                        // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                        if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                        $parentA->getGene($swapPoint-3) == "D$day"."T".($timeslot-1) && ($swapPoint != 0)){
                                            $swapPoint = $swapPoint-3;
                                            // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                            if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                            $parentA->getGene($swapPoint-3) == "D$day"."T".($timeslot-1) && ($swapPoint != 0)){
                                                $swapPoint = $swapPoint-3;
                                                // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                                if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                                $parentA->getGene($geneCheckPointFinale-3) == "D$day"."T".($timeslot-1) && ($swapPoint != 0)){                                            
                                                    $swapPoint = $swapPoint-3;
                                                    // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                                    if($roomId == $parentA->getGene($swapPoint-2) && $profId == $parentA->getGene($swapPoint-1) &&
                                                    $parentA->getGene($swapPoint-3) == "D$day"."T".($timeslot-1) && ($swapPoint != 0)){                                                
                                                        $swapPoint = $swapPoint-3;
                                                        // print"TimeslotIncrementCheck: ".$parentA->getGene($swapPoint-3)."->".$parentA->getGene($swapPoint)." ?\n";
                                                    } else if($swapPoint == 0){
                                                        continue;
                                                    }
                                                } else if($swapPoint == 0){
                                                    continue;
                                                }
                                            } else if($swapPoint == 0){
                                                continue;
                                            }
                                        } else if($swapPoint == 0){
                                            continue;
                                        }
                                    } else if($geneCheckPointFinale == 0){
                                        continue;
                                    }

                            }
                        }
                    }
                // ---------------------------------- End : Swap Point Duration Checker --------------------------------- //
    
                print"NewSwapPoint = ".$swapPoint."\n";
                for ($j = 0; $j < $parentA->getChromosomeLength(); $j++) {
                    if ($j < $swapPoint) {
                        // print "gene 497 = ".$parentA->getGene(497);
                        // print "\nj > SwapPoint = ".$j." > ".$swapPoint."\n";
                        $offspring->setGene($j, $parentA->getGene($j));
                    } else {
                        $offspring->setGene($j, $parentB->getGene($j));
                    }
                }
    
                // Debugging output
                // print "New Population with crossover:\n";
                // print $newPopulation."\n";
    
                $newPopulation->setIndividual($i, $offspring);
            } else {
                // Add to population without crossover
                // print "New Population without crossover:\n";
                // print $newPopulation."\n";
                $newPopulation->setIndividual($i, $parentA);
            }
        }
    
        return $newPopulation;
    }
    

    /**
     * Perform a mutation on the individuals of the given population
     *
     * @param Population $population The population to mutate
     */
    public function mutatePopulation($population, $timetable)
    {
        $newPopulation = new Population();
        $bestFitness = $population->getFittest(0)->getFitness();

        

        for ($i = 0; $i < $population->size(); $i++) {
            $individual = $population->getFittest($i);
            $randomIndividual = new Individual($timetable);

            // Calculate adaptive mutation rate
            $adaptiveMutationRate = $this->mutationRate;

            if ($individual->getFitness() > $population->getAvgFitness()) {
                $fitnessDelta1 = $bestFitness - $individual->getFitness();
                $fitnessDelta2 = $bestFitness - $population->getAvgFitness();
                $adaptiveMutationRate = ($fitnessDelta1 / $fitnessDelta2) * $this->mutationRate;
            }

            if ($i > $this->elitismCount) {
                for ($j = 0; $j < $individual->getChromosomeLength(); $j++) {
                    $random = mt_rand() / mt_getrandmax();

                    if (($adaptiveMutationRate * $this->temperature) > $random) {                   

                    //---------------------------- Start of Duration Fixer ----------------------------//
                        
                        $gene = $randomIndividual->getGene($j);
                        $geneprimary = $gene;
                            // Regular expression pattern to match D#T# format
                        $pattern = '/^D\d+T\d+$/';

                        // // Perform the regex match
                        if (preg_match($pattern, $geneprimary) && $j < ($individual->getChromosomeLength()-3)) {
                            $day = substr($gene, 1, 1); // Get the character at index 1 (0-based index)
                            $timeslot = substr($gene, 3, 1); // Get the character at index 3 (0-based index)

                            // Convert the extracted characters to integers
                            $day = intval($day);
                            $timeslot = intval($timeslot);

                            // echo "Gene".$j."[";
                            // echo "D".$day."T".$timeslot.",";
                            
                            $roomId = $randomIndividual->getGene($j+1);
                            $profId = $randomIndividual->getGene($j+2);

                            // echo "$roomId,$profId,";
                            // echo "],";

                            $gene2hr = $randomIndividual->getGene($j+3);
                            $day2hr = substr($gene2hr, 1, 1); // Get the character at index 1 (0-based index)
                            $timeslot2hr = substr($gene2hr, 3, 1); // Get the character at index 3 (0-based index)  
                            // Convert the extracted characters to integers                          
                            $day2hr = intval($day2hr);
                            $timeslot2hr = intval($timeslot2hr);
                            // room & prof
                            $roomId2hr = $randomIndividual->getGene($j+4);
                            $profId2hr = $randomIndividual->getGene($j+5); 
                            
                            if($j > 0){
                                if(($roomId == $roomId2hr && $profId == $profId2hr && $gene2hr == "D$day"."T".($timeslot+1)) ||
                                ($roomId == $randomIndividual->getGene($j-2) && $profId == $randomIndividual->getGene($j-1) &&
                                    $randomIndividual->getGene($j-3) == "D$day"."T".($timeslot-1))){
                                        // print "Next Duration\n";
                                        continue;     
                                }           
                                else{                                
                                    $individual->setGene($j, $randomIndividual->getGene($j));                             
                                }
                            }
                            else if ($j == 0){
                                if($roomId == $roomId2hr && $profId == $profId2hr && $gene2hr == "D$day"."T".($timeslot+1)){
                                        // print "0 Duration\n";
                                        continue;     
                                }            
                                else{                                
                                    $individual->setGene($j, $randomIndividual->getGene($j));                             
                                }
                            }
                            else{
                                continue;
                            }
                        }
                        else if(preg_match($pattern, $geneprimary) && $j > ($individual->getChromosomeLength()-4)){
                            $day = substr($gene, 1, 1); // Get the character at index 1 (0-based index)
                            $timeslot = substr($gene, 3, 1); // Get the character at index 3 (0-based index)

                            // Convert the extracted characters to integers
                            $day = intval($day);
                            $timeslot = intval($timeslot);

                            // echo "Gene".$j."[";
                            // echo "D".$day."T".$timeslot.",";
                            
                            $roomId = $randomIndividual->getGene($j+1);
                            $profId = $randomIndividual->getGene($j+2);
                            $testID = $randomIndividual->getGene(495);
                            $testID2 = $randomIndividual->getGene($individual->getChromosomeLength()-3);

                            // echo "$roomId,$profId,";
                            // echo "],";

                            // echo "\nTEST GENE:".$testID." =? ".$testID2." || ";

                            if ($roomId == $randomIndividual->getGene($j-2) && $profId == $randomIndividual->getGene($j-1) &&
                                    $randomIndividual->getGene($j-3) == "D$day"."T".($timeslot-1)){
                                        // print "After Duration\n";
                                        continue;     
                                }           
                                else{                                
                                    $individual->setGene($j, $randomIndividual->getGene($j));                             
                                }

                        }
                        else{
                            $individual->setGene($j, $randomIndividual->getGene($j)); 
                        }
                        
                    //---------------------------- End of Duration Fixer ----------------------------//

                    }
                }
            }

            // print "New Population:\n".$newPopulation."\n";
            $newPopulation->setIndividual($i, $individual);
        }

        return $newPopulation;
    }
}
