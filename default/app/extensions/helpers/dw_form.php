<?php
/**
 *
 * Extension para el manejo de formularios que hereda atributos de la clase Form
 *
 * @category    Views
 * @package     Helpers
 */

class DwForm extends Form {
    
     /**
     * Contador para los labels, checbox y radios
     * @var int
     */
    protected static $_counter = 0;
    
    /**
     * Contador para los formularios abiertos
     * @var int
     */
    protected static $_form = 1;
    
    /**
     * Identificación del formulario abierto
     * @var array
     */
    protected static $_name = array();
    
    /**
     * Tipo de estilo de formulario
     * @var string
     */
    protected static $_style='form-vertical';
        
    /**
     * Variable que indica si muestra el label
     * @var boolean
     */
    protected static $_show_label = false;

    /**
     * Variable que indica si muestra el help block
     * @var boolean
     */
    protected static $_help_block = false;
    
    /**
     * Método que utiliza los atributos de un input o form para aplicar parámetros por defecto
     * 
     * @param array $attrs
     * @param string $type
     * @return string
     */
    protected static function _getAttrsClass($attrs, $type) {
        if($type==='form' OR $type==='form-multipart') {
            $formAjax = (APP_AJAX && Session::get('app_ajax')) ? TRUE : FALSE;
            if(isset($attrs['class'])) {
                if(preg_match("/\bno-ajax\b/i", $attrs['class'])) {
                    $formAjax = FALSE;
                }                
                //Verifico si está definida la clase para ajax, pero si no se encuentra el aplicativo para ajax
                if(preg_match("/\bjs-remote\b/i", $attrs['class']) && !$formAjax) {
                    $formAjax = TRUE;
                }
                //Verifico si el aplicativo está con ajax
                if($formAjax==TRUE) {
                    //Verifico si está definida la clase para ajax
                    if(!preg_match("/\bjs-remote\b/i", $attrs['class'])) {
                        $attrs['class'] = 'js-remote '.$attrs['class'];
                    }                   
                }
            } else {
                //Asigno que pertenece a la clase de validación y si utiliza ajax
                $attrs['class'] = ($formAjax) ? 'js-validate js-remote' : 'js-validate';
            }

            if($formAjax && !isset($attrs['data-to'])) { //Si es un form con ajax verifico si está definido el data-to
                $attrs['data-to'] = 'shell-content';
            }
            if(!isset($attrs['id'])) { //Verifico si está definido el id
                $attrs['id'] = 'form-'.self::$_form;
            }
            if(!isset($attrs['name'])) { //Verifico si está definido el name
                $attrs['name'] = $attrs['id'];
            }
            //Verifico el estilo de formulario
            self::setStyleForm((isset($attrs['form-style'])) ? $attrs['form-style'] : 'form-vertical');            
            //Mantengo la información del formulario
            self::$_name['id']      = $attrs['id'];
            self::$_name['name']    = $attrs['name'];
            //asigno el estilo al formulario
            $attrs['class']         = $attrs['class'].' '.self::$_style;
            self::$_form++;

        } else {
            if(isset($attrs['class'])) {
                //Verifico si está la clase form-control
                if(!preg_match("/\bform-control\b/i", $attrs['class'])) {
                    $attrs['class'] = 'form-control '.$attrs['class'];
                }
            } else {
                //Si no está definida las clases las asigno según el tipo
                $attrs['class'] = ( ($type != 'checkbox') && ($type != 'radio') ) ? "form-control span12 " : "";
            }            
            //Verifico si se utiliza la mayúscula solo para los text y textarea
            if( ($type=='text') OR ($type=='textarea') ) {                
                if( (APP_MAYUS && !preg_match("/\binput-lower\b/i", $attrs['class']) ) OR preg_match("/\binput-upper\b/i", $attrs['class']) ) {
                    $attrs['onchange'] = !isset($attrs['onchange']) ? 'this.value=this.value.toUpperCase()' : rtrim($attrs['onchange'],';').'; this.value=this.value.toUpperCase()';
                }
            }
            //Reviso si es readonly
            if(preg_match("/\binput-readonly\b/i", $attrs['class'])) {
                $attrs['readonly'] = 'readonly';
            }
            //Reviso si esta deshabilitado
            if(preg_match("/\binput-disabled\b/i", $attrs['class'])) {
                $attrs['disabled'] = 'disabled';
            }
            //Verifico si es requerido
            if(preg_match("/\binput-required\b/i", $attrs['class'])) {
                $attrs['required'] = 'required';
            }
            //Verifico el data-action del input (cuando utiliza ajax)
            if(isset($attrs['data-action'])) {
                $attrs['data-action'] = PUBLIC_PATH.trim($attrs['data-action'], '/').'/';
            }
        }
        return $attrs;
    }
    
    /**
     * Método para obtener el id y el nombre de un campo bajo el patrón modelo.campo
     * 
     * @param string $field
     * @return array
     */
    protected static function _getFieldName($field) {
        $formField = explode('.', $field, 2);
        if(isset($formField[1])) {
            $id     = "{$formField[0]}_{$formField[1]}";
            $name   = "{$formField[0]}[{$formField[1]}]";
        } else {
            $id     = "{$formField[0]}";
            $name   = "{$formField[0]}";
        }
        return array('id' => $id, 'name' => $name);
    }
    
    /**
     * Método para setear el stilo del formulario
     */
    public static function setStyleForm($style='form-vertical') {
        self::$_style = $style;    
        //Valido si se muestra el label o el help block según el tipo de formulario
        self::$_show_label = (self::$_style=='form-search' OR self::$_style=='form-inline') ? FALSE : TRUE;
        self::$_help_block = (self::$_style=='form-search') ? FALSE : TRUE;
    }
    
    /**
     * Método para abrir y cerrar un div controls en los input
     * 
     * @staticvar boolean $i
     * @return string
     */
    public static function getControls() {
        if(self::$_style=='form-horizontal') {
            static $i = true;
            if($i==false) {
                $i = true;
                return '</div>'.PHP_EOL;
            }
            $i = false;
            return '<div class="col-md-10">'.PHP_EOL;
        }
        return null;
    }
    
    /**
     * Método para generar automáticamente las etiquetas <label> de los input
     * 
     * @param string $label Texto a mostrar
     * @param string $field Nombre del campo asignado
     * @param array $attrs Atributos de la etiqueta
     * @param boolean $req Indica si se muestra el campo como requerido
     * @param string $type Nombre del tipo de input: radio, checkbox o textarea     
     * @return string
     */
    public static function label($text, $field, $attrs=NULL, $req='', $type='text') {
        //Extraigo el id y name
        if(!empty($field)) {
            extract(self::_getFieldName($field));
        }
        //Verifico si tiene atributos
        if(is_array($attrs)) {
            //Reviso si esta deshabilitado
            if(!preg_match("/\bcontrol-label\b/i", $attrs['class'])) {
                $attrs['class'] = $attrs['class'].' control-label';
            }
        } else {
            $attrs = array('class'=>'control-label');
        }
        
        if(self::$_style == 'form-horizontal') {
            $attrs['class'] =  'col-md-2 '.$attrs['class'];
        }
        
        $label = '';
        if($text!='') {
            $id = (empty($id)) ? NULL : $id; //Por si el field=NULL
            //Si es checkbox o radio
            if( ($type == 'checkbox') or ($type == 'radio') ) {
                $type = (self::$_style != 'form-horizontal' OR preg_match("/\binline\b/i", $req) ) ? $type.' inline' : $type;
                $id = str_replace(array('[', ']'), '_', $id);
                $label.= "<label for=\"$id".self::$_counter."\" class=\"$type\">$text";
                self::$_counter++;
            } else {
                $attrs = Tag::getAttrs($attrs);
                $label.= "<label for=\"$id\" $attrs>$text";
            }
            //Verifico si es requerido
            $label.= (preg_match("/\binput-required\b/i", $req)) ? '<span class="req">*</span>' : '';
            $label .= "</label>";
        }
        return $label;
    }
    
    /**
     * Método que devuelve el help-block de un input
     * 
     * @param string $help Texto a mostrar
     * @param string $field Nombre del campo
     * @return string
     */
    public static function help($help, $field='') {
        if($field) {
            //Extraigo el id y name
            extract(self::_getFieldName($field));
        }
        //Se arma el help
        $help = "<p class=\"help-block\">$help ";
        $help.= "<small class=\"help-error\"></small>";
        $help.= '</p>';        
        return $help;
    }
    
    /**
     * Abre una etiqueta de formulario
     * 
     * @param string $action Lugar al que envía
     * @param string $method Método de envío
     * @param string $attrs Atrributos     
     * @return string
     */
    public static function open($action=null, $method='post', $attrs=null) {
        
        $form = '';
        $attrs = self::_getAttrsClass($attrs, 'form'); //Verifico los atributos
        
        //Verifico si se valida (en caso de que no se mande el fomrmulario con ajax)        
        if( (preg_match("/\bjs-validate\b/i", $attrs['class'])) && !preg_match("/\bjs-remote\b/i", $attrs['class']) ) {
            $form.= self::_getValidationForm();
        }
        
        if($method=='') {
            $method= 'post';
        }
        $tmp_m = $method;
        
        if(empty($action)) {
            extract(Router::get());
            $action = ($module)  ? "$module/$controller/$action/" : "$controller/$action/";
            if($parameters) {
                $action.= join('/', $parameters).'/';
            }
        }
        
        $form.= parent::open($action, $tmp_m, $attrs);//Obtengo la etiqueta para abrir el formulario
        return $form.PHP_EOL;
    }
    
    /**
     * Método para aplicar el foco a un input
     * 
     * @param string $field Nombre del campo: modelo.campo
     * @return string
     */
    public static function focus($field) {
        //Extraigo el id
        extract(self::_getFieldName($field));
        return '<script text="type/javascript">$(function() { $("#'.$id.'").focus(); });</script>';
    }
        
    /**
     * Método que genera un input date
     * 
     * @param type $field Nombre del input
     * @param type $attrs Atributos del input
     * @param type $value Valor por defecto
     * @param type $label Detalle de la etiqueta label
     * @param type $help Descripción del campo     
     * @return string
     */
    public static function date($field, $attrs=null, $value=null, $label='', $help='') {        
        //Tomo los nuevos atributos definidos en las clases
        $attrs = self::_getAttrsClass($attrs, 'date');
        //Armo el input
        $input = self::getControls();
        if(self::$_style=='form-search' OR self::$_style=='form-inline') {
            $attrs['placeholder'] = $label;
        }                  
        if(empty($attrs['data-format'])) {
            $attrs['data-format'] = "yyyy-mm-dd";
        }
        //Verifico si está definida la máscara mask-date
        if(!preg_match("/\bmask-date\b/i", $attrs['class'])) {
            $attrs['class'] = 'mask-date '.$attrs['class'];
        }
        //Armo el input del form
        if(!IS_DESKTOP) {
            $input.= '<div class="input-group date">';
            $input.= parent::text($field, $attrs, $value, 'date');
        } else {
            $input.= '<div class="input-group date datepicker">';
            //Verifico si está definida la clase input-date
            if(!preg_match("/\binput-date\b/i", $attrs['class'])) {
                $attrs['class'] = 'input-date '.$attrs['class'];
            }
            $attrs['class'] = 'js-datepicker '.$attrs['class'];            
            $input.= parent::text($field, $attrs, $value);
        }        
        $input.= '<span class="input-group-addon"><i class="fa fa-calendar"></i></span>';                        
        $input.= '</div>';
        //Verifico si el formato del formulario muestra el help
        if(self::$_help_block && $help) {
            $input.= self::help($help);
        }
        //Cierro el controls
        $input.= self::getControls();
        if(!self::$_help_block) {
            return $input.PHP_EOL;
        }
        //Verifico si tiene un label
        $label = ($label && self::$_show_label) ? self::label($label, $field, null, $attrs['class'])  : '';
        return '<div class="form-group">'.$label.$input.'</div>'.PHP_EOL;
    }
    
    
    /**
     * Método que genera un input text basandose en el bootstrap de twitter
     * @param type $field Nombre del input
     * @param type $attrs Atributos del input
     * @param type $value Valor por defecto
     * @param type $label Detalle de la etiqueta label
     * @param type $help Descripción del campo
     * @param type $type tipo de campo (text, numeric, etc)
     * @return string
     */
    public static function text($field, $attrs=null, $value=null, $label='', $help='', $type='text') {
        //Tomo los nuevos atributos definidos en las clases
        $attrs = self::_getAttrsClass($attrs, $type);
        //Armo el input
        $input = self::getControls();
        if(self::$_style=='form-search' OR self::$_style=='form-inline') {
            $attrs['placeholder'] = $label;
        }  
        $prepend = FALSE;
        //Verifico si tiene un prepend
        if(isset($attrs['input-group'])) {            
            $input.= '<div class="input-group">';
            $input.= '<span class="input-group-addon">'.$attrs['input-group'].'</span>';
            $prepend = TRUE;
            unset($attrs['input-group']);            
        }
        //Armo el input del form
        $input.= parent::text($field, $attrs, $value, $type);
        if($prepend) {
            $input.= '</div>';
        }
        //Verifico si el formato del formulario muestra el help
        if(self::$_help_block) {
            $input.= self::help($help);
        }
        //Cierro el controls
        $input.= self::getControls();
        if(!self::$_help_block) {
            return $input.PHP_EOL;
        }
        //Verifico si tiene un label
        $label = ($label && self::$_show_label) ? self::label($label, $field, null, $attrs['class'])  : '';
        return '<div class="form-group">'.$label.$input.'</div>'.PHP_EOL;
    }
    
    
    /**
     * Método que genera un input tipo password
     * 
     * @param type $field Nombre del input
     * @param type $attrs Atributos del input
     * @param type $value Valor por defecto
     * @param type $label Detalle de la etiqueta label
     * @param type $help Descripción del campo     
     * 
     * @return string
     */
    public static function pass($field, $attrs=null, $value=null, $label='', $help='') {
        //Tomo los nuevos atributos definidos en las clases
        $attrs = self::_getAttrsClass($attrs, 'pass');
        //Armo el input
        $input = self::getControls();
        if(self::$_style=='form-search' OR self::$_style=='form-inline') {
            $attrs['placeholder'] = $label;
        }
        //Armo el input del form
        $input.= parent::pass($field, $attrs, $value);
        //Verifico si el formato del formulario muestra el help
        if(self::$_help_block) {
            $input.= self::help($help);
        }
        //Cierro el controls
        $input.= self::getControls();
        if(!self::$_help_block) {
            return $input.PHP_EOL;
        }
        //Verifico si tiene un label
        $label = ($label && self::$_show_label) ? self::label($label, $field, null, $attrs['class'])  : '';
        return '<div class="form-group">'.$label.$input.'</div>'.PHP_EOL;
    }
    /**
     * Método para crear un select a partir de un array de objetos de ActiveRecord. <br />
     * Permite mostrar varios valores por fila y valor con slug
     * 
     * @param string $field Nombre del select: modelo.campo
     * @param string, array $show Campo a mostrar de la consulta.  Es posible mostrar mas de un campo con array('campo1', 'campo2')
     * @param object $data Array de objetos. Puede dejarse nulo y carga automáticamente la data o indicar el modelo, método y parámetros
     * @param string|array $blank Texto a mostrar en blanco
     * @param array $attrs Atributos del input
     * @param string $value Valor del select
     * @param string $label Texto a mostrar en la etiqueta <label>
     * @param boolean $help Texto de descripción del campo
     * @return string
     */
    public static function dbSelect($field, $show=null, $data=null, $blank='Selección', $attrs=null, $value=null, $label='', $help='') {

        $attrs = self::_getAttrsClass($attrs, 'select');
        if(empty($data)) {
            $data = array(''=>'Selección');
        }

        if(empty($blank)) {
            $blank = 'Selección';
        }

        $attrs2 = $attrs;

        $input = self::getControls();

        if(is_array($attrs)) { //Cargo los atributos
            $attrs = Tag::getAttrs($attrs);
        }
        
        list($id, $name, $value) = self::getFieldData($field, $value);

        $options = '';

        //Muestro el blank
        if(!empty($blank) && $blank != 'none') {
            if(is_array($blank)) {
                $options_key = @array_shift(array_keys($blank));
                $options = '<option value="'.$options_key.'">' . htmlspecialchars($blank[$options_key], ENT_COMPAT, APP_CHARSET) . '</option>';
            } else {
                $options = '<option value="">' . htmlspecialchars($blank, ENT_COMPAT, APP_CHARSET) . '</option>';
            }
        }
        //Verifico si existe una data
        if($data === null){
            //por defecto el modelo de modelo(_id)
            $model_asoc = explode('.', $field, 2);
            $model_asoc = substr(end($model_asoc), 0, -3);//se elimina el _id
            $model_name = $model_asoc; //Tomo el nombre del modelo
            $model_asoc = Load::model($model_asoc); //Cargo el modelo
            $pk = $model_asoc->primary_key[0];//Tomo la llave primaria
            if(!$show){
                $show = $model_asoc->non_primary[0]; //por defecto el primer campo no pk
            }
            $data = $model_asoc->find("columns: $pk,$show","order: $show asc");//mejor usar array
        } else if(isset($data[0]) && is_string($data[0])) { //Verifico si ha enviado el modelo, método y/o parámetros
            $model_name = explode('/', $data[0]); //Tomo el nombre del modelo
            $model_name = end($model_name);
            $model_asoc = Load::model($data[0]);//Cargo el modelo
            $pk = $model_asoc->primary_key[0];//Tomo la llave primaria
            // Verifica si existe el argumento
            if(isset($data[2]) && isset($data[3])) {
                $data = $model_asoc->$data[1]($data[2],$data[3]);
            } else if(isset($data[2])) {
                $data = $model_asoc->$data[1]($data[2]);
            } else {
                $data = $model_asoc->$data[1]();
            }
        } else { //Si ha enviado una data determino la llave primaria
            $model_asoc = explode('.', $field, 2);
            $model_name = $model_asoc[0];
            $tam = strlen(end($model_asoc));
            $pk = substr(end($model_asoc), $tam-2, $tam);//se utiliza el id
        }
        //Recorro la data
        foreach($data as $p) {
            //Muestro el valor del id como show value, a menos que tenga un {nombre_modelo}_slug
            $slug = $model_name."_slug";
            if(is_array($show) && in_array($slug, $show)) {
                $show_value = (isset($p->$slug)) ? $p->$slug : $p->$pk; //Verifico si existe un campo llamado {nombre_modelo}_slug, lo tomo sino la pk
            } else {
                $show_value = $p->$pk;
            }
            $options .= "<option value=\"$show_value\"";
            if($show_value == $value) {
                $options .= ' selected="selected"';
            }
            if(is_array($show)) { //Verifico si se muestran varios campos
                $opt = '';
                $i=0;
                foreach($show as $item) {
                    if($show[$i] != $slug) {
                        if(isset($p->{$show[$i]})) {
                            $opt.= htmlspecialchars($p->$item, ENT_COMPAT, APP_CHARSET). ' | ';
                        } else {
                            $opt.= htmlspecialchars($show[$i], ENT_COMPAT, APP_CHARSET). ' | ';
                        }
                    }
                    $i++;
                }
                $options .= '>' . trim($opt, ' | '). '</option>';
            } else {
                $options .= '>' . htmlspecialchars($p->$show, ENT_COMPAT, APP_CHARSET). '</option>';
            }
        }
        $input.=  "<select id=\"$id\" name=\"$name\" $attrs>$options</select>";
        if(self::$_help_block) {
            $input.= self::help($help);
        }
        $input.= self::getControls();
        if(!self::$_help_block) {
            return $input.PHP_EOL;
        }
        //Verifico si tiene un label
        $label = ($label && self::$_show_label) ? self::label($label, $field, null, $attrs2['class'])  : '';
        return '<div class="form-group">'.$label.$input.'</div>'.PHP_EOL;

    }
    
    
    /**
     * Método que genera un campo select
     * 
     * @param type $field Nombre del input
     * @param array $data Datos a mostrar
     * @param type $attrs Atributos para el select
     * @param type $value Valor por defecto
     * @param type $label Texto a mostrar en la etiqueta label
     * @param type $help Texto a mostrar en el help-block
     * 
     * @return string
     */
    public static function select($field, $data=array(), $attrs = NULL, $value = NULL, $label='', $help='') {        
        $attrs = self::_getAttrsClass($attrs, 'select');
        if(empty($data)) {
            $data = array(''=>'Selección');
        }
        $input = self::getControls();
        $input.= parent::select($field, $data, $attrs, $value);
        if(self::$_help_block) {
            $input.= self::help($help);
        }
        $input.= self::getControls();
        if(!self::$_help_block) {
            return $input.PHP_EOL;
        }
        //Verifico si tiene un label
        $label = ($label && self::$_show_label) ? self::label($label, $field, null, $attrs['class'])  : '';
        return '<div class="form-group">'.$label.$input.'</div>'.PHP_EOL;
    }
    
    /**
     * Método para crear un input tipo textarea
     * 
     * @param string $field Nombre del input
     * @param array $attrs Atributos del input
     * @param string $value Valor del input
     * @param string $label Texto a mostrar en la etiqueta <label>
     * @param string $help Texto a mostrar como descripcion
     * @return string
     */
    public static function textarea($field, $attrs=null, $value=null, $label='', $help='') {
        //Tomo los nuevos atributos definidos en las clases
        $attrs = self::_getAttrsClass($attrs, 'textarea');
        //Armo el input
        $input = self::getControls();
        if(self::$_style=='form-search' OR self::$_style=='form-inline') {
            $attrs['placeholder'] = $label;
        }        
        //Tomo el input del form
        $input.= parent::textarea($field, $attrs, $value);
        //Verifico si el formato del formulario muestra el help
        if(self::$_help_block) {
            $input.= self::help($help);
        }
        //Cierro el controls
        $input.= self::getControls();
        if(!self::$_help_block) {
            return $input.PHP_EOL;
        }

        //Verifico si tiene un label
        $label = ($label && self::$_show_label) ? self::label($label, $field, null, $attrs['class'])  : '';
        return '<div class="form-group">'.$label.$input.'</div>'.PHP_EOL;
    }
    
    /**
     * Método para abrir/cerrar un fieldset
     * @staticvar boolean $i
     * @param type $text Texto a mostrar del fieldset
     * @param type $attrs
     * @return string
     */
    public static function fieldset($text='', $attrs=null){
        static $i = true;
        if($i==false) {
            $i = true;
            return '</fieldset>';
        }
        if (is_array($attrs)) {
            $attrs = Tag::getAttrs($attrs);
        }
        $i = false;
        return "<fieldset $attrs><legend>$text</legend>";
    }

    /**
     * Método para crear un legend
     * @param type $text
     * @param type $attrs
     * @return type
     */
    public static function legend($text, $attrs = NULL) {
        if (is_array($attrs)) {
            $attrs = Tag::getAttrs($attrs);
        }
        return "<legend $attrs>$text</legend>";
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
        
    /**
     * Método para mostrar el botón para enviar un formulario
     * 
     * @param string $title
     * @param string $icon
     * @param array $attrs
     * @param string $text
     * @return strig
     */
    public static function send($title='Guardar registro', $icon='fa-save', $attrs=array(), $text='guardar') {
        return DwButton::save($title, $icon, $attrs, $text);
    }
    
    /**
     * Método para mostrar el botón de cancelar
     * 
     * @param string $redir
     * @param string $title
     * @param string $icon
     * @return strig
     */
    public static function cancel($redir=NULL, $title='', $icon='fa-ban') {
        return DwButton::cancel($redir, $title, $icon);
    }
    
    /**
     * Método para mostrar el botón de resetear el formulario
     * 
     * @param string $icon
     * @return strig
     */
    public static function reset($icon='fa-undo') {
        return DwButton::reset(self::$_name['id'], FALSE, $icon);
    }    
    
    /**
     * Método para generar un token en los formularios
     */
    public static function token() {
        $h      = date("G")>12 ? 1 : 0;
        $time   = uniqid().mktime($h, 0, 0, date("m"), date("d"), date("Y"));
        $key    = sha1($time);
        Session::set('rsa32_key',$key);
        return self::hidden('rsa32_key', NULL, $key);
    }

    /**
     * Devuelve el resultado del token almacenado en sesion con la enviada en el form
     * @return boolean
     */
    public static function isValidToken() {
        $key = Session::get('rsa32_key');
        if( (!is_null($key) ) && ($key === Input::post('rsa32_key')) ) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Crea un botón tipo submit
     *
     * @param string $text Texto del botón
     * @param array $attrs Atributos de campo (opcional)
     * @return string
     */
    public static function btnSubmit($text, $attrs = NULL, $icon = Null) {
        if (is_array($attrs)) {
            $attrs = Tag::getAttrs($attrs);
        }
        $btn = '';
        if($icon) {
            $btn.='<i class="fa fa-pd-expand '.$icon.'"></i>';
        }
        $btn.= $text;
        return "<button type=\"submit\" $attrs>$btn</button>";
    }
}
