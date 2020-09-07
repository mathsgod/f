<?php


class _b extends F\Page
{
    public function get()
    {
        $this->redirect("a");
        $this->write("b");
    }
}
