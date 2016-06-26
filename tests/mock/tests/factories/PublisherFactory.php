<?php
return array(
    'attributes' => array(),
    'iwanami' => array(
        'relations' => array(
            'Series' => array(
                array(
                    'name' => 'Iwanami library',
                    'relations' => array('Books' => array(
                        array('name' => 'The letters of Vincent Van Gogh'),
                        array('name' => 'The Critic of Pure Reason'),
                        array('name' => 'Discourse on Method')
                    ))
                ),
                array(
                    'name' => 'Iwanami science library',
                    'relations' => array('Books' => array(
                        array('name' => 'What is the "Elements" by Euclid?'),
                        array('name' => 'Why does Human draw a picture?'),
                    ))
                ),
            )
        )
    )
);
