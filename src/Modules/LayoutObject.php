<?php

/**
 * Properties are private by design.
 * You should never edit an object. Just create a new one as you need to remove the old object and add the new one.
 */

namespace PRISM\Modules;

/**
 * This is another example of using getters/setters with no encapsulated
 * functionality. x,y,z should likely be bare properties, or adjusted as above,
 * by simply creating a new instance with the new values.
 *
 * By contrast setHeading is actually encapsulating a guarantee that a heading
 * with the appropriate range is passed in.
 * @phpstan-type LayoutObjectData array{flags?: mixed, index?: mixed, heading: float}|array{heading: float}
 */
class LayoutObject
{
    public const UNPACK = 'sX/sY/CZ/CFlags/CIndex/CHeading';
    public const PACK = 'ssCCCC';

    private float $x;
    private float $y;
    private float $z;

    /** @var float|int<-180,180> $hdng */
    private mixed $hdng;

    /** TODO: Not totally certain what these three datatypes are. */
    private int $index = -1;
    private mixed $type;
    private mixed $typeData = 0; /** object index / circle radius / checkpoint half-width */
    private int $colour = 0;

    public static string $OBJ_REAL_OBJECT      = 'object';
    public static string $OBJ_RESTRICTED       = 'restricted_area';
    public static string $OBJ_MARSHALL         = 'marshall';
    public static string $OBJ_MARSHALL_LEFT    = 'marshall_left';
    public static string $OBJ_MARSHALL_RIGHT   = 'marshall_right';
    public static string $OBJ_ROUTE_CHECK      = 'route_check';
    public static string $OBJ_START            = 'start';
    public static string $OBJ_CHECK_1          = 'check_1';
    public static string $OBJ_CHECK_2          = 'check_2';
    public static string $OBJ_CHECK_3          = 'check_3';
    public static string $OBJ_FINISH           = 'finish';

    /**
     * Constructor.
     * Either pass an 8 Byte data structure provided by insim or in the layout file or all other params as well.
     * @param string|float $dataOrX 8 byte data or value for X coordinate
     * @param float $y Y coordinate
     * @param float $z Z coordinate
     * @param string $type one of LayoutObject::$OBJ_... values TODO: Convert to BackedEnum to support value-of<BackedEnum> typing.
     * @param float|int<-180, 180> $hdng heading in degree or route index
     * @param mixed $typeData object index, circle radius or half-width of control point
     * @param int $colour Colour for chalk and tyre stacks
     */
    public function __construct(string|float $dataOrX, $y = null, $z = null, $type = null, $hdng = null, $typeData = null, $colour = null)
    {
        if ($type == null) {
            $this->initFromBytes((string) $dataOrX);
            return;
        }

        $this->x = (float) $dataOrX;
        $this->y = $y;
        $this->z = $z;
        $this->type = $type;
        if ($this->type == self::$OBJ_ROUTE_CHECK) {
            $this->setRouteIndex($hdng);
        } else {
            $this->setHeading($hdng);
        }
        $this->typeData = $typeData;
        $this->colour = $colour;
    }

    public function __toString()
    {
        $retVal = $this->type;

        if ($this->type == self::$OBJ_REAL_OBJECT) {
            $retVal .= ': ' . self::$REV_AXO[$this->typeData];
            if ($this->typeData >= self::$AXO_CHALK_LINE && $this->typeData <= self::$AXO_CHALK_RIGHT3) {
                $retVal .= ', col:' . self::$REV_CC[$this->colour];
            } elseif ($this->typeData >= self::$AXO_TYRE_SINGLE && $this->typeData <= self::$AXO_TYRE_STACK4_BIG) {
                $retVal .= ', col:' . self::$REV_TC[$this->colour];
            }
        }
        return $retVal;
    }

    public function pack(): string
    {
        $x = $this->rawX();
        $y = $this->rawY();
        $z = $this->z * 4;

        $dat = $this->dataForType();
        // The following two lines need to be ignored unless flags/index can be guaranteed parameters.
        $flags = $dat['flags']; /** @phpstan-ignore-line */
        $index = $dat['index']; /** @phpstan-ignore-line */
        $heading = $dat['heading'];

        return pack(LayoutObject::PACK, $x, $y, $z, $flags, $index, $heading);
    }

    public function rawX(): float
    {
        return round($this->x * 16);
    }

    public function rawY(): float
    {
        return round($this->y * 16);
    }

    /**
     * Alternative constructor path.
     *
     * @param string $data
     * @return void
     */
    public function initFromBytes(string $data): void
    {
        $obj = unpack(LayoutObject::UNPACK, $data);
        // scale ...
        if (!$obj) {
            throw new \Exception("Could not unpack LayoutObject from: ${data}");
        }
        $this->x = $obj['X'] / 16;
        $this->y = $obj['Y'] / 16;
        $this->z = $obj['Z'] / 4;
        $this->setHeading($obj['Heading'] / 256 * 360);

        $ix = $obj['Index'];
        $this->index = $ix;
        $flg = $obj['Flags'];

        if ($ix >= 192) { // either a circle or an unknown object
            if ($ix == 254) { // it's a marshall circle
                if (($flg & 0x80) != 0) { // highest bit set : restricted area
                    // HeadingByte has the same meaning as it does for all objects
                    // Flags byte contains the following data :
                    // bits 0 to 1 :
                    switch ($flg & 0x03) {
                        // 00 = no marshall
                        case 0:
                            $this->type = self::$OBJ_RESTRICTED;
                            break;
                            // 01 = standing marshall
                        case 1:
                            $this->type = self::$OBJ_MARSHALL;
                            break;
                            // 10 = marshall pointing left
                        case 2:
                            $this->type = self::$OBJ_MARSHALL_LEFT;
                            break;
                            // 11 = marshall pointing right
                        case 3:
                            $this->type = self::$OBJ_MARSHALL_RIGHT;
                            break;
                    }
                    // bits 2 to 5 :
                    // circle diameter in metres (shifted left by 2 bits)
                    $this->typeData = ($flg >> 2) & 0x07;
                } else { // highest bit of flags is not set : route checker
                    $this->type = self::$OBJ_ROUTE_CHECK;
                    // HeadingByte is used not for heading, but the route index
                    // bits 2 to 5 :
                    // circle diameter in metres (shifted left by 2 bits)
                    $this->typeData = ($flg >> 2) & 0x0F;
                    $this->hdng = $obj['Heading'];
                }
            } else {
                // unknown object - ignore
                $this->type = array($flg, $ix);
            }
        } else { // could be an actual object or a control object
            if(($flg & 0x80) != 0) { // highest bit set : control object
                // bit 6 : never set
                // bit 7 : always set (0x80)
                // bits 2 to 5 :
                // Checkpoint width(half width!) in metres (shifted left by 2 bits)
                $this->typeData = (($flg >> 2) & 0x07);
                // Flags byte contains the following data :
                // bits 0 to 1 :
                switch ($flg & 0x03) {
                    // 00 = Start position (if width = 0) or finish line (if width > 0)
                    case 0:
                        if ($this->typeData == 0) {
                            $this->type = self::$OBJ_START;
                        } else {
                            $this->type = self::$OBJ_FINISH;
                        }
                        break;
                        // 01 = Checkpoint 1
                    case 1:
                        $this->type = self::$OBJ_CHECK_1;
                        break;
                        // 10 = Checkpoint 2
                    case 2:
                        $this->type = self::$OBJ_CHECK_2;
                        break;
                        // 11 = Checkpoint 3
                    case 3:
                        $this->type = self::$OBJ_CHECK_3;
                        break;
                }
            } else { // highest bit of flags is not set : autocross object
                $this->type = self::$OBJ_REAL_OBJECT;
                // save flag as colour (even if it's just tyres and chalk)
                echo 'flag is:'.$flg;
                $this->colour = $flg;
                $this->typeData = $ix;
            }
        }
    }

    /**
     * Computes the index flag and heading fields for the current object.
     * @return LayoutObjectData Array containing the value for 'flags', 'index' and 'heading'
     */
    public function dataForType(): array
    {
        $dat = array();

        switch($this->type) {
            case self::$OBJ_RESTRICTED:
                $dat['flags'] = $this->typeData << 2 & 0x3C | 0x80;
                $dat['index'] = 254;
                break;
            case self::$OBJ_MARSHALL:
                $dat['flags'] = $this->typeData << 2 & 0x3C | 0x81;
                $dat['index'] = 254;
                break;
            case self::$OBJ_MARSHALL_LEFT:
                $dat['flags'] = $this->typeData << 2 & 0x3C | 0x82;
                $dat['index'] = 254;
                break;
            case self::$OBJ_MARSHALL_RIGHT:
                $dat['flags'] = $this->typeData << 2 & 0x3C | 0x83;
                $dat['index'] = 254;
                break;
            case self::$OBJ_ROUTE_CHECK:
                $dat['flags'] = $this->typeData << 2 & 0x3C;
                $dat['index'] = 255;
                $dat['heading'] = $this->hdng;
                break;

            case self::$OBJ_REAL_OBJECT:
                $dat['flags'] = $this->colour;
                $dat['index'] = $this->typeData;
                break;
            case self::$OBJ_START:
                $dat['flags'] = 0x80;
                $dat['index'] = 0;
                break;
            case self::$OBJ_CHECK_1:
                $dat['flags'] = $this->typeData << 2 & 0x3C | 0x81;
                $dat['index'] = 0;
                break;
            case self::$OBJ_CHECK_2:
                $dat['flags'] = $this->typeData << 2 & 0x3C | 0x82;
                $dat['index'] = 0;
                break;
            case self::$OBJ_CHECK_3:
                $dat['flags'] = $this->typeData << 2 & 0x3C | 0x83;
                $dat['index'] = 0;
                break;
            case self::$OBJ_FINISH:
                $dat['flags'] = $this->typeData << 2 & 0x3C | 0x80;
                $dat['index'] = 0;
                break;

            default:
                // unknown objects...
                if (is_array($this->type)) {
                    $dat['flags'] = $this->type[0];
                    $dat['index'] = $this->type[1];
                }
        }

        // default heading calculation
        if (!isset($dat['heading'])) {
            $dat['heading'] = ($this->hdng/* + 180*/) * 256 / 360;
        }

        return $dat;
    }

    /**
     * Set the heading in deg.
     * Normalizes the heading to use the lfs notation between -180° and 180°.
     * @param float|int $val
     */
    private function setHeading($val): void
    {
        if ($this->type == self::$OBJ_ROUTE_CHECK) {
            throw new \Exception('Trying to set heading on route checker object. Use setRouteIndex instead.');
        }

        if (!is_numeric($val)) {
            throw new \Exception('Invalid heading index.');
        }

        // val must be between -180 and 180
        $deg = $val;
        while ($deg < -179) {
            $deg += 360;
        }
        while ($deg > 180) {
            $deg -= 360;
        }

        $this->hdng = $deg;
    }

    /**
     * Set the route index if the object is a route check.
     */
    private function setRouteIndex(float|int $val): void
    {
        if ($this->type != self::$OBJ_ROUTE_CHECK) {
            throw new \Exception('Trying to set route index on a non route checker object. Use setHeading instead.');
        }

        if (!is_int($val) || $val < 0 || $val > 90) { // TODO check what's the actual max value
            throw new \Exception('Invalid route index.');
        }

        $this->hdng = $val;
    }

    public function x(): float
    {
        return $this->x;
    }
    public function setX(float $x): void
    {
        $this->x = $x;
    }

    public function y(): float
    {
        return $this->y;
    }
    public function setY(float $y): void
    {
        $this->y = $y;
    }
    public function z(): float
    {
        return $this->z;
    }
    public function setZ(float $z): void
    {
        $this->z = $z;
    }
    public function heading(): mixed
    {
        return $this->hdng;
    }
    public function type(): mixed
    {
        return $this->type;
    }
    public function typeData(): mixed
    {
        return $this->typeData;
    }
    public function colour(): mixed
    {
        return $this->colour;
    }
    public function index(): int
    {
        if ($this->index == -1) {
            $dat = $this->dataForType();
            $this->index = $dat['index']; /** @phpstan-ignore-line */
        }

        return $this->index;
    }

    public static int $AXO_CHALK_LINE = 4;
    public static int $AXO_CHALK_LINE2 = 5;
    public static int $AXO_CHALK_AHEAD = 6;
    public static int $AXO_CHALK_AHEAD2 = 7;
    public static int $AXO_CHALK_LEFT = 8;
    public static int $AXO_CHALK_LEFT2 = 9;
    public static int $AXO_CHALK_LEFT3 = 10;
    public static int $AXO_CHALK_RIGHT = 11;
    public static int $AXO_CHALK_RIGHT2 = 12;
    public static int $AXO_CHALK_RIGHT3 = 13;
    public static int $AXO_14 = 14;
    public static int $AXO_15 = 15;
    public static int $AXO_16 = 16;
    public static int $AXO_17 = 17;
    public static int $AXO_18 = 18;
    public static int $AXO_19 = 19;
    public static int $AXO_CONE_RED = 20;
    public static int $AXO_CONE_RED2 = 20;
    public static int $AXO_CONE_RED3 = 22;
    public static int $AXO_CONE_BLUE = 23;
    public static int $AXO_CONE_BLUE2 = 24;
    public static int $AXO_CONE_GREEN = 25;
    public static int $AXO_CONE_GREEN2 = 26;
    public static int $AXO_CONE_ORANGE = 27;
    public static int $AXO_CONE_WHITE = 28;
    public static int $AXO_CONE_YELLOW = 29;
    public static int $AXO_CONE_YELLOW2 = 30;
    public static int $AXO_31 = 31;
    public static int $AXO_32 = 32;
    public static int $AXO_33 = 33;
    public static int $AXO_34 = 34;
    public static int $AXO_35 = 35;
    public static int $AXO_36 = 36;
    public static int $AXO_37 = 37;
    public static int $AXO_38 = 38;
    public static int $AXO_39 = 39;
    public static int $AXO_CONE_PTR_RED = 40;
    public static int $AXO_CONE_PTR_BLUE = 41;
    public static int $AXO_CONE_PTR_GREEN = 42;
    public static int $AXO_CONE_PTR_YELLOW = 43;
    public static int $AXO_44 = 44;
    public static int $AXO_45 = 45;
    public static int $AXO_46 = 46;
    public static int $AXO_47 = 47;
    public static int $AXO_TYRE_SINGLE = 48;
    public static int $AXO_TYRE_STACK2 = 49;
    public static int $AXO_TYRE_STACK3 = 50;
    public static int $AXO_TYRE_STACK4 = 51;
    public static int $AXO_TYRE_SINGLE_BIG = 52;
    public static int $AXO_TYRE_STACK2_BIG = 53;
    public static int $AXO_TYRE_STACK3_BIG = 54;
    public static int $AXO_TYRE_STACK4_BIG = 55;
    public static int $AXO_56 = 56;
    public static int $AXO_57 = 57;
    public static int $AXO_58 = 58;
    public static int $AXO_59 = 59;
    public static int $AXO_60 = 60;
    public static int $AXO_61 = 61;
    public static int $AXO_62 = 62;
    public static int $AXO_63 = 63;
    public static int $AXO_MARKER_CURVE_L = 64;
    public static int $AXO_MARKER_CURVE_R = 65;
    public static int $AXO_MARKER_L = 66;
    public static int $AXO_MARKER_R = 67;
    public static int $AXO_MARKER_HARD_L = 68;
    public static int $AXO_MARKER_HARD_R = 69;
    public static int $AXO_MARKER_L_R = 70;
    public static int $AXO_MARKER_R_L = 71;
    public static int $AXO_MARKER_S_L = 72;
    public static int $AXO_MARKER_S_R = 73;
    public static int $AXO_MARKER_S2_L = 74;
    public static int $AXO_MARKER_S2_R = 75;
    public static int $AXO_MARKER_U_L = 76;
    public static int $AXO_MARKER_U_R = 77;
    public static int $AXO_78 = 78;
    public static int $AXO_79 = 79;
    public static int $AXO_80 = 80;
    public static int $AXO_81 = 81;
    public static int $AXO_82 = 82;
    public static int $AXO_83 = 83;
    public static int $AXO_DIST25 = 84;
    public static int $AXO_DIST50 = 85;
    public static int $AXO_DIST75 = 86;
    public static int $AXO_DIST100 = 87;
    public static int $AXO_DIST125 = 88;
    public static int $AXO_DIST150 = 89;
    public static int $AXO_DIST200 = 90;
    public static int $AXO_DIST250 = 91;
    public static int $AXO_92 = 92;
    public static int $AXO_93 = 93;
    public static int $AXO_94 = 94;
    public static int $AXO_95 = 95;
    public static int $AXO_ARMCO1 = 96;
    public static int $AXO_ARMCO3 = 97;
    public static int $AXO_ARMCO5 = 98;
    public static int $AXO_99 = 99;
    public static int $AXO_100 = 100;
    public static int $AXO_101 = 101;
    public static int $AXO_102 = 102;
    public static int $AXO_103 = 103;
    public static int $AXO_BARRIER_LONG = 104;
    public static int $AXO_BARRIER_RED = 105;
    public static int $AXO_BARRIER_WHITE = 106;
    public static int $AXO_107 = 107;
    public static int $AXO_108 = 108;
    public static int $AXO_109 = 109;
    public static int $AXO_110 = 110;
    public static int $AXO_111 = 111;
    public static int $AXO_BANNER1 = 112;
    public static int $AXO_BANNER2 = 113;
    public static int $AXO_114 = 114;
    public static int $AXO_115 = 115;
    public static int $AXO_116 = 116;
    public static int $AXO_117 = 117;
    public static int $AXO_118 = 118;
    public static int $AXO_119 = 119;
    public static int $AXO_RAMP1 = 114;
    public static int $AXO_RAMP2 = 115;
    public static int $AXO_122 = 122;
    public static int $AXO_123 = 123;
    public static int $AXO_124 = 124;
    public static int $AXO_125 = 125;
    public static int $AXO_126 = 126;
    public static int $AXO_127 = 127;
    public static int $AXO_SPEED_HUMP_10M = 128;
    public static int $AXO_SPEED_HUMP_6M = 129;
    public static int $AXO_130 = 130;
    public static int $AXO_131 = 131;
    public static int $AXO_132 = 132;
    public static int $AXO_133 = 133;
    public static int $AXO_134 = 134;
    public static int $AXO_135 = 135;
    public static int $AXO_POST_GREEN = 136;
    public static int $AXO_POST_ORANGE = 137;
    public static int $AXO_POST_RED = 138;
    public static int $AXO_POST_WHITE = 139;
    public static int $AXO_140 = 140;
    public static int $AXO_141 = 141;
    public static int $AXO_142 = 142;
    public static int $AXO_143 = 143;
    public static int $AXO_BALE = 144;
    public static int $AXO_145 = 145;
    public static int $AXO_146 = 146;
    public static int $AXO_147 = 147;
    public static int $AXO_RAILING = 148;
    public static int $AXO_START_LIGHTS = 149;
    public static int $AXO_150 = 150;
    public static int $AXO_151 = 151;
    public static int $AXO_152 = 152;
    public static int $AXO_153 = 153;
    public static int $AXO_154 = 154;
    public static int $AXO_155 = 155;
    public static int $AXO_156 = 156;
    public static int $AXO_157 = 157;
    public static int $AXO_158 = 158;
    public static int $AXO_159 = 159;
    public static int $AXO_SIGN_KEEP_LEFT = 160;
    public static int $AXO_SIGN_KEEP_RIGHT = 161;
    public static int $AXO_162 = 162;
    public static int $AXO_163 = 163;
    public static int $AXO_164 = 164;
    public static int $AXO_165 = 165;
    public static int $AXO_166 = 166;
    public static int $AXO_167 = 167;
    public static int $AXO_SIGN_SPEED_80 = 168;
    public static int $AXO_SIGN_SPEED_50 = 169;
    public static int $AXO_170 = 170;
    public static int $AXO_171 = 171;
    public static int $AXO_CONCRETE_SLAB = 172;
    public static int $AXO_CONCRETE_RAMP = 173;
    public static int $AXO_CONCRETE_WALL = 174;
    public static int $AXO_CONCRETE_PILLAR = 175;
    public static int $AXO_CONCRETE_SLAB_WALL = 176;
    public static int $AXO_CONCRETE_RAMP_WALL = 177;
    public static int $AXO_CONCRETE_SHORT_SLAB_WALL = 178;
    public static int $AXO_CONCRETE_WEDGE = 179;
    public static int $AXO_180 = 180;
    public static int $AXO_181 = 181;
    public static int $AXO_182 = 182;
    public static int $AXO_183 = 183;
    public static int $AXO_START_POSITION = 184;
    public static int $AXO_PIT_START_POINT = 185;
    public static int $AXO_PIT_STOP_BOX = 186;
    public static int $AXO_187 = 187;
    public static int $AXO_188 = 188;
    public static int $AXO_189 = 189;
    public static int $AXO_190 = 190;
    public static int $AXO_191 = 191;

    public static int $TC_BLACK = 0;
    public static int $TC_WHITE = 1;
    public static int $TC_RED = 2;
    public static int $TC_BLUE = 3;
    public static int $TC_GREEN = 4;
    public static int $TC_YELLOW = 5;

    public static int $CC_WHITE = 0;
    public static int $CC_RED = 1;
    public static int $CC_BLUE = 2;
    public static int $CC_YELLOW = 3;

    /** @var string[] $REV_AXO */
    public static array $REV_AXO = array('AXO_NULL','AXO_1','AXO_2','AXO_3','AXO_CHALK_LINE','AXO_CHALK_LINE2','AXO_CHALK_AHEAD','AXO_CHALK_AHEAD2','AXO_CHALK_LEFT','AXO_CHALK_LEFT2','AXO_CHALK_LEFT3','AXO_CHALK_RIGHT','AXO_CHALK_RIGHT2','AXO_CHALK_RIGHT3','AXO_14','AXO_15','AXO_16','AXO_17','AXO_18','AXO_19','AXO_CONE_RED','AXO_CONE_RED2','AXO_CONE_RED3','AXO_CONE_BLUE','AXO_CONE_BLUE2','AXO_CONE_GREEN','AXO_CONE_GREEN2','AXO_CONE_ORANGE','AXO_CONE_WHITE','AXO_CONE_YELLOW','AXO_CONE_YELLOW2','AXO_31','AXO_32','AXO_33','AXO_34','AXO_35','AXO_36','AXO_37','AXO_38','AXO_39','AXO_CONE_PTR_RED','AXO_CONE_PTR_BLUE','AXO_CONE_PTR_GREEN','AXO_CONE_PTR_YELLOW','AXO_44','AXO_45','AXO_46','AXO_47','AXO_TYRE_SINGLE','AXO_TYRE_STACK2','AXO_TYRE_STACK3','AXO_TYRE_STACK4','AXO_TYRE_SINGLE_BIG','AXO_TYRE_STACK2_BIG','AXO_TYRE_STACK3_BIG','AXO_TYRE_STACK4_BIG','AXO_56','AXO_57','AXO_58','AXO_59','AXO_60','AXO_61','AXO_62','AXO_63','AXO_MARKER_CURVE_L','AXO_MARKER_CURVE_R','AXO_MARKER_L','AXO_MARKER_R','AXO_MARKER_HARD_L','AXO_MARKER_HARD_R','AXO_MARKER_L_R','AXO_MARKER_R_L','AXO_MARKER_S_L','AXO_MARKER_S_R','AXO_MARKER_S2_L','AXO_MARKER_S2_R','AXO_MARKER_U_L','AXO_MARKER_U_R','AXO_78','AXO_79','AXO_80','AXO_81','AXO_82','AXO_83','AXO_DIST25','AXO_DIST50','AXO_DIST75','AXO_DIST100','AXO_DIST125','AXO_DIST150','AXO_DIST200','AXO_DIST250','AXO_92','AXO_93','AXO_94','AXO_95','AXO_ARMCO1','AXO_ARMCO3','AXO_ARMCO5','AXO_99','AXO_100','AXO_101','AXO_102','AXO_103','AXO_BARRIER_LONG','AXO_BARRIER_RED','AXO_BARRIER_WHITE','AXO_107','AXO_108','AXO_109','AXO_110','AXO_111','AXO_BANNER1','AXO_BANNER2','AXO_114','AXO_115','AXO_116','AXO_117','AXO_118','AXO_119','AXO_RAMP1','AXO_RAMP2','AXO_122','AXO_123','AXO_124','AXO_125','AXO_126','AXO_127','AXO_SPEED_HUMP_10M','AXO_SPEED_HUMP_6M','AXO_130','AXO_131','AXO_132','AXO_133','AXO_134','AXO_135','AXO_POST_GREEN','AXO_POST_ORANGE','AXO_POST_RED','AXO_POST_WHITE','AXO_140','AXO_141','AXO_142','AXO_143','AXO_BALE','AXO_145','AXO_146','AXO_147','AXO_RAILING','AXO_START_LIGHTS','AXO_150','AXO_151','AXO_152','AXO_153','AXO_154','AXO_155','AXO_156','AXO_157','AXO_158','AXO_159','AXO_SIGN_KEEP_LEFT','AXO_SIGN_KEEP_RIGHT','AXO_162','AXO_163','AXO_164','AXO_165','AXO_166','AXO_167','AXO_SIGN_SPEED_80','AXO_SIGN_SPEED_50','AXO_170','AXO_171','AXO_CONCRETE_SLAB','AXO_CONCRETE_RAMP','AXO_CONCRETE_WALL','AXO_CONCRETE_PILLAR','AXO_CONCRETE_SLAB_WALL','AXO_CONCRETE_RAMP_WALL','AXO_CONCRETE_SHORT_SLAB_WALL','AXO_CONCRETE_WEDGE','AXO_180','AXO_181','AXO_182','AXO_183','AXO_START_POSITION','AXO_PIT_START_POINT','AXO_PIT_STOP_BOX','AXO_187','AXO_188','AXO_189','AXO_190','AXO_191');
    /** @var string[] $REV_TC */
    public static array $REV_TC = array('TC_BLACK','TC_WHITE','TC_RED','TC_BLUE','TC_GREEN','TC_YELLOW');
    /** @var string[] $REV_CC */
    public static array $REV_CC = array('CC_WHITE','CC_RED','CC_BLUE','CC_YELLOW');

    /** @var array<int, string> $Objects */
    public static $Objects = array(
        0 => 'Scenery Object',
        4 => 'Long Chalk Line',
        5 => 'Short Chalk Line',
        6 => 'Short Ahead Arrow',
        7 => 'Long Ahead Arrow',
        8 => 'Short Left Curve Arrow',
        9 => 'Left Turn Arrow',
        10 => 'Long Left Curve Arrow',
        11 => 'Short Right Curve Arrow',
        12 => 'Right Turn Arrow',
        13 => 'Long Right Curve Arrow',
        20 => 'Red+White Cone',
        21 => 'Red Cone',
        22 => 'Striped Red Cone',
        23 => 'Striped Blue Cone',
        24 => 'Blue Cone',
        25 => 'Striped Green Cone',
        26 => 'Green Cone',
        27 => 'Orange Cone',
        28 => 'White Cone',
        29 => 'Striped Yellow Cone',
        30 => 'Yellow Cone',
        40 => 'Red Directional Cone',
        41 => 'Blue Directional Cone',
        42 => 'Green Directional Cone',
        43 => 'Yellow Directional Cone',
        48 => 'Single Tire',
        49 => 'Tire Stack of 2',
        50 => 'Tire Stack of 3',
        51 => 'Tire Stack of 4',
        52 => 'Big Single Tire',
        53 => 'Big Tire Stack of 2',
        54 => 'Big Tire Stack of 3',
        55 => 'Big Tire Stack of 4',
        64 => 'Left Curve Marker',
        65 => 'Right Curve Marker',
        66 => 'Left Turn Marker',
        67 => 'Right Turn Marker',
        68 => 'Hard Left Turn Marker',
        69 => 'Hard Right Turn Marker',
        70 => 'Left->Right Road Marker',
        71 => 'Right->Left Road Marker',
        72 => 'U-Turn->Right Turn Marker',
        73 => 'U-Turn->Left Turn Marker',
        74 => 'Left Winding Turn Marker',
        75 => 'Right Winding Turn Marker',
        76 => 'Left U-Turn Marker',
        77 => 'Right U-Turn Marker',
        84 => '25m Sign',
        85 => '50m Sign',
        86 => '75m Sign',
        87 => '100m Sign',
        88 => '125m Sign',
        89 => '150m Sign',
        90 => '200m Sign',
        91 => '250m Sign',
        96 => 'Short Railing',
        97 => 'Medium Railing',
        98 => 'Long Railing',
        104 => 'Long Barrier',
        105 => 'Red Barrier',
        106 => 'White Barrier',
        112 => 'Banner',
        113 => 'Banner',
        120 => 'Ramp',
        121 => 'Wide Ramp',
        128 => '10m Speed Bump',
        129 => '6m Speed Bump',
        136 => 'Green Post',
        137 => 'Orange Post',
        138 => 'Red Post',
        139 => 'White Post',
        144 => 'Hay Bale',
        148 => 'Railing',
        160 => 'Keep Left Sign',
        161 => 'Keep Right Sign',
        168 => '80 KM/H Sign',
        169 => '50 KM/H Sign',
        172 => 'Concrete Slab',
        173 => 'Concrete Ramp',
        174 => 'Concrete Wall',
        175 => 'Concrete Pillar',
        176 => 'Concrete Slab Wall',
        177 => 'Concrete Ramp Wall',
        178 => 'Concrete Short Slab Wall',
        179 => 'Concrete Wedge'
    );
}
