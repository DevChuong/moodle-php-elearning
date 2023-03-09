<?php

// UPDATE/INSERT COHORTs
    protected $file;
    protected function insert_examcode_to_new_table(){
        ...
        return $id;
    }
    protected function create_examcode($file){
        return $this->insert_examcode_to_new_table();
    }
    protected function create_cohort_after_valid_conditions_applied(){
        ...
        return $id;
    }
    public function create_cohort($examcodeid,$file){
        return $this->create_cohort_after_valid_conditions_applied();
    }
    protected function condition_if_cohort_is_applied_create_enrolment(){
        ...
        if( $check_enrol ){ // bài hoc chưa tạo
            return $check_enrol->id;
        } else {
            ...
            return $enrol->id;
        }
    }
    public function check_enrol($idnumber,$cohortid){
        return $this->condition_if_cohort_is_applied_create_enrolment();
    }
    protected function insert_examcode_old_table_old_case_applied(){
        ...
    }
    public function create_cohort_examcode($file,$cohortid,$examcodeid){
        $this->insert_examcode_old_table_old_case_applied();
    }
    protected $cohort;
    protected function upload_related_entities(){
        ...
        } else {
            return $return_cohort_id;
        }
    }
    public function full_process($cohort){
        return $this->upload_related_entities();
    }
