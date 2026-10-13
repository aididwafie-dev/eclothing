export type DropdownOption = { id: string; value: string };

export type PersonalDetails = {
  s_id: string;
  name: string;
  service: string | null;
  ketukangan_type: number;
  tred: string | null;
  pangkat: string | null;
  unit: string | null;
  gender: string | null;
  telephone_number: string;
  duty_status: string | null;
  religion: string | null;
  address_line1: string;
  address_city: string;
  address_state: string;
  address_postcode: string;
  nama_waris: string;
  telephone_number_waris: string;
  name_tag: string;
  unit_lama: string;
  kem_lama: string;
  spl_lama: string;
};

export type ProfileResponse = {
  personalDetails: PersonalDetails | null;
  position: string;
  dropdowns: {
    services: DropdownOption[];
    tredOfficer: DropdownOption[];
    tredRecruit: DropdownOption[];
    tredBoth: DropdownOption[];
    units: DropdownOption[];
    genders: DropdownOption[];
    dutyStatuses: DropdownOption[];
    states: DropdownOption[];
  };
};

export type ClothingSizeItem = {
  clothesId: string;
  clothesType: string;
  sizeKind: "select" | "text";
  sizeOptions: string[];
  placeholder: string | null;
  savedValue: string;
};

export type ClothingSizeGroup = {
  uniform: { id: string; uniformType: string; uniformName: string | null };
  items: ClothingSizeItem[];
};

export type ClothingSizesResponse = {
  groups: ClothingSizeGroup[];
};
